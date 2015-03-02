<?php

    namespace kyra\sm\models;

    use yii\base\Exception;
    use yii\db\Connection;
    use Yii;

    class SiteMenu
    {
        const TYPE_STATIC = 1;
        const TYPE_CONTROLLER = 2;
        const TYPE_LINK = 3;

        private $db = null;

        public function __construct(Connection $db)
        {
            $this->db = $db;
        }

        public static function IsValidType($param)
        {
            $types = array(self::TYPE_STATIC, self::TYPE_CONTROLLER, self::TYPE_LINK);
            return in_array($param, $types);
        }

        public function AddMenu($params)
        {
            switch ($params['Type'])
            {
                case SiteMenu::TYPE_STATIC:
                    return $this->AddStaticMenu($params);
                case SiteMenu::TYPE_CONTROLLER:
                    return $this->AddControllerMenu($params);
                case SiteMenu::TYPE_LINK :
                    return $this->AddLinkMenu($params);
                default:
                    throw new Exception('Not implemented');
            }
        }

        private function AddLinkMenu($params)
        {
            $sql = 'INSERT INTO sitemenu (MenuType, Title, Url) VALUES (:type, :title, :url)';
            $ret = $this->db->createCommand($sql, [':type' => SiteMenu::TYPE_LINK,
                ':title' => $params['Title'],
                ':url' => empty($params['URL']) ? null : $params['URL'],
            ])->execute();

            if ($ret)
                return $this->db->lastInsertID;
            else
                return false;

        }

        private function AddStaticMenu($params)
        {
            $sql = 'INSERT INTO sitemenu (MenuType, PageID, Title, Url, Params) VALUES (:type, :pid, :title, :url, :params)';
            $ret = $this->db->createCommand($sql,
                [':type' => SiteMenu::TYPE_STATIC,
                    ':pid' => $params['PageID'],
                    ':title' => $params['Title'],
                    ':url' => $params['UrlKey'],
                    ':params' => json_encode($params)
                ])->execute();

            if ($ret)
                return $this->db->lastInsertID;
            else
                return false;
        }

        private function AddControllerMenu($params)
        {
            if (is_string($params['Url']))
            {
                $value = $params['Url'];
                $params['Url'] = array($value);
            }
            $sql = 'INSERT INTO sitemenu (MenuType, Title, Url, Params) VALUES (:type, :title, :url, :params)';
            $ret = $this->db->createCommand($sql,
                [':type' => SiteMenu::TYPE_CONTROLLER,
                    ':title' => $params['Title'],
                    ':url' => $params['Url'][0],
                    ':params' => json_encode($params)
                ])->execute();

            if ($ret)
                return $this->db->lastInsertID;
            else
                return false;
        }

        public function GetMenuFlat()
        {
            $sql = 'SELECT m.* FROM sitemenu AS m '
                . 'LEFT JOIN page AS p ON m.PageID=p.PageID '
                . 'ORDER BY ParentID, SortOrder';
            $rows = $this->db->createCommand($sql)->queryAll();
            return $rows;
        }

        public function GetMenuTree()
        {
            $rows = $this->GetMenuFlat();
            $tree = self::ParseTree(0, $rows, 'SMID', 'ParentID');
            return $tree;
        }

        public static function ParseTree($root, $tree, $idName, $pidName, $additionalParams = array())
        {
            $return = array();
            # Traverse the tree and search for direct children of the root
            foreach ($tree as $idx => $node)
            {
                $parent = $node[$pidName];
                # A direct child is found
                if ($parent == $root)
                {
                    # Remove item from tree (we don't need to traverse this again)
                    unset($tree[$idx]);
                    # Append the child into result array and parse it's children
                    $p = array('payload' => $node,
                        'parent' => $parent,
                        'children' => self::parseTree($node[$idName], $tree, $idName, $pidName, $additionalParams));

                    foreach ($additionalParams as $key => $val) $p[$key] = $val;

                    $return[] = $p;
                }
            }
            return empty($return) ? array() : $return;
        }

        public function UpdateMenu($items)
        {
            if (empty($items) || !is_array($items))
            {
                $sql = 'TRUNCATE TABLE sitemenu';
                $this->db->createCommand($sql)->execute();
                return true;
            }
            $transaction = $this->db->beginTransaction();
            try
            {
                $ids = array();
                foreach ($items as $item) $ids[] = $item['id'];
                $sql = 'DELETE FROM sitemenu WHERE SMID NOT IN (' . implode(', ', $ids) . ')';
                $this->db->createCommand($sql)->execute();

                $i = 1;
                foreach ($items as $item)
                {
                    $sql = 'UPDATE sitemenu SET ParentID=:parent, SortOrder=:order WHERE SMID=:id LIMIT 1';
                    $this->db->createCommand($sql,
                        [':parent' => $item['parent'],
                            ':order' => $i,
                            ':id' => $item['id']])->execute();
                    $i++;
                }

                $transaction->commit();
                return true;
            } catch (Exception $ex)
            {
                $transaction->rollback();
                return false;
            }
        }

        public function ConvertToMenuItems($tree)
        {
            $ret = array();
            if (!is_null($tree) && count($tree) > 0)
            {
                foreach ($tree as $node)
                {
                    $payload = $node['payload'];
                    $label = $payload['Title'];
                    if ($payload['MenuType'] == self::TYPE_STATIC)
                        $url = $this->CreateUrl($payload);
                    else if ($payload['MenuType'] == self::TYPE_LINK)
                    {
                        $url = $payload['Url'];
                    } else
                    {
                        $obj = json_decode($payload['Params'], true);
                        $url = $obj['Url'];
                    }

                    $tmp = ['label' => $label,
                        'visible' => true,
                        'url' => $url];
//                    if (!empty($payload['PageID']))
//                    {
//                        $canAccess = self::CanAccess(Yii::app()->user->getRole(), $payload['AccessRole']);
//                        if (!$canAccess) $tmp['visible'] = false;
//                    }
                    if ($tmp['visible'])
                        $tmp['items'] = $this->ConvertToMenuItems($node['children']);
                    $ret[] = $tmp;
                }
            }
            return $ret;
        }

        public function CreateUrl($payload)
        {
            switch ($payload['MenuType'])
            {
                case self::TYPE_STATIC :
                    return ['/sm/page/view', 'key' => $payload['Url']];
                case self::TYPE_LINK :
                    return $payload['Url'];
                default :
                    throw new Exception('AAAA');
            }
        }

        public function UpdateMenuTitle($pid, $title)
        {
            $sql = 'UPDATE sitemenu SET Title=:title WHERE PageID=:pid';
            $this->db->createCommand($sql, [':title' => $title, ':pid' => $pid])->execute();
        }

        public function GetParents($pid)
        {
//            $sql = 'SELECT * FROM sitemenu WHERE PageID=:pid';
//            $row = $this->db->createCommand($sql, [':pid' => $pid])->queryRow();
//            if(empty($row)) return [];

            $sql = 'SELECT sm.*
                    FROM sitemenu AS sm
                    JOIN page AS p ON sm.PageID=p.PageID ';

            $rows = $this->db->createCommand($sql)->queryAll();
            $indexed = array();
            $smid = array();
            foreach ($rows as $row)
            {
                $indexed[$row['PageID']] = $row;
                $smid[$row['SMID']] = $row;
            }

            $ret = array();
            $id = $pid;
            while (true)
            {
                if(!isset($indexed[$id]) && !isset($smid[$id])) return [];
                $ret[] = isset($indexed[$id]) ? $indexed[$id] : $smid[$id];
                if (empty($indexed[$id]['ParentID'])) break;
                $id = $indexed[$id]['ParentID'];
            }

            $ret = array_reverse($ret);
            return $ret;
        }

        public function GetMenuTitle($pid)
        {
            $sql = 'SELECT Title FROM sitemenu WHERE PageID=:pid AND PageID IS NOT NULL';
            $title = $this->db->createCommand($sql)->queryScalar(array(':pid' => $pid));
            return $title;
        }

    }