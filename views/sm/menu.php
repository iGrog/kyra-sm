<?php
    use kyra\sm\models\SiteMenu;
    use kyra\sm\SiteMenuAsset;
    use yii\helpers\Url;

    SiteMenuAsset::register($this);

?>
<table width="100%" id="mvm">
    <tr>
        <td width="40%" valign="top">
            <h3>Страницы сайта доступные для меню</h3>
<!--            <button data-bind="click: CreateNew" class="btn btn-success">Создать новую статик страницу</button>-->
            <ul data-bind="foreach: StaticPages" style="margin-top:10px;">
                <li>
                    <a data-bind="attr: { href: $root.GetEditUrl($data) }, visible: Type() == <?= SiteMenu::TYPE_STATIC; ?>">edit</a>
                    <span data-bind="text: Title"></span>
                    <button data-bind="click: $root.AddToMenu" class="btn btn-xs btn-info "> → в меню</button>
                    <button data-bind="click: $root.RemovePage" class="btn btn-xs btn-danger ">x</button>
                </li>
            </ul>

            <h3>Добавить простую ссылку</h3>
            <label>URL</label><br/>
            <input type="text" class="form-control" data-bind="value: URL"/>
            <label>Название</label>
            <input type="text" class="form-control" data-bind="value: Title"/>

            <button class="btn btn-success" data-bind="click: AddLink" style="margin-top: 5px;">Добавить простую
                ссылку
            </button>

        </td>
        <td width="60%" valign="top">
            <h3>Меню сайта</h3>

            <div id="menuTree">

                <?php

                    function printTree($tree)
                    {
                        if (!is_null($tree) && count($tree) > 0)
                        {
                            echo '<ul class="dd-list">';
                            foreach ($tree as $node)
                            {
                                $payload = $node['payload'];
                                echo '<li class="dd-item" data-id="' . $payload['SMID'] . '"><div>' . $payload['Title']
                                        . ((!empty($payload['PageID']))
                                                ? '<a href="' .  Url::to(['/sm/sm/edit', 'id' => $payload['PageID']]) . '" class="btn btn-warning btn-xs">edit</a>'
                                                : '')
                                        . '<button class="btn btn-danger btn-xs delete">x</button>'
                                        . '</div>';
                                printTree($node['children']);
                                echo '</li>';
                            }
                            echo '</ul>';
                        }
                    }

                    if (!empty($tree)) printTree($tree);
                    else echo '<ul class="dd-list"></ul>';

                ?>

            </div>
            <button id="save" class="btn btn-success">Записать</button>
        </td>
    </tr>
</table>

<script type="text/javascript">

    var $menu = null;

    var MenuViewModel = function ()
    {
        var self = this;
        self.URL = ko.observable('');
        self.Title = ko.observable('');
        self.AddLink = function ()
        {
            var title = self.Title().trim();
            if (title == '')
            {
                $.gritter.add({
                    title: 'Ошибка',
                    text: 'Должно быть хотя бы название'
                });
                return;
            }
            var obj = {
                Title: self.Title(),
                URL: self.URL(),
                Type: <?=SiteMenu::TYPE_LINK; ?>
            };
            obj[csrfToken] = csrfValue;

            $.post('<?=Url::to(['/sm/sm/add-to-menu']); ?>', obj, function (json)
            {
                $.gritter.add({
                    title: 'Info',
                    text: 'Пункт меню добавлен'
                });

                $('<li data-id="' + json.SMID + '"><div>' + obj.Title + '<button class="btn btn-danger btn-xs delete">x</button></div>').appendTo($menu);
            }, 'json');
        };
        self.StaticPages = ko.observableArray([]);
        self.LoadStaticPages = function ()
        {
            $.getJSON('<?=Url::to(['/sm/sm/load-sp']); ?>', function (json)
            {
                ko.mapping.fromJS(json, {}, self.StaticPages);
            });
        };
        self.CreateNew = function ()
        {
            var obj = {};
            obj[csrfToken] = csrfValue;
            $.post('<?=Url::to(['/sm/sm/create-new']); ?>', obj, function (json)
            {
                if (!json.hasError)
                {
                    $.gritter.add({
                        title: 'Info',
                        text: 'Страница создана'
                    });
                    self.LoadStaticPages();
                }
            }, 'json');
        };
        self.AddToMenu = function (item)
        {
            item[csrfToken] = csrfValue;
            $.post('<?=Url::to(['/sm/sm/add-to-menu']); ?>', item, function (json)
            {
                $.gritter.add({
                    title: 'Info',
                    text: 'Пункт меню добавлен'
                });
                var title = ko.utils.unwrapObservable(item.Title);
                $('<li data-id="' + json.SMID + '"><div>' + title + '<button class="btn btn-danger btn-xs delete">x</button></div>').appendTo($menu);
            }, 'json');
        };

        self.RemovePage = function (item)
        {
            if (!confirm('Действительно удалить эту страницу?')) return;
            item[csrfToken] = csrfValue;
            $.post('<?=Url::to(['/sm/sm/removepage']); ?>', item, function (json)
            {
                $.gritter.add({
                    title: 'Info',
                    text: 'Страница удалена'
                });
                self.LoadStaticPages();
            }, 'json');

        };

        self.GetEditUrl = function (item)
        {
            var id = ko.utils.unwrapObservable(item.PageID);
            return '<?=Url::to(['sm/sm/editpage']); ?>?pid=' + id;
        };
    };

    var mvm = new MenuViewModel();
    ko.applyBindings(mvm, $('#mvm')[0]);

    var csrfToken = '';
    var csrfValue = '';

    $(document).ready(function ()
    {
        csrfToken = $('meta[name=csrf-param]').attr('content');
        csrfValue = $('meta[name=csrf-token]').attr('content');

        mvm.LoadStaticPages();
        $menu = $('#menuTree ul').first();

        $menu.nestedSortable({
            handle: 'div',
            items: 'li',
            toleranceElement: '> div',
            placeholder: 'placeholder',
            listType: 'ul',
            isTree: true
        });

        $(document).on('click', 'button.delete', function ()
        {
            if (confirm('Действительно удалить пункт меню?'))
                $(this).closest('li').remove();
        });

        $('#save').on('click', function ()
        {
            var obj = { menu: $menu.nestedSortable('toPlainArray') };
            obj[csrfToken] = csrfValue;
            $.post('<?=Url::to(['/sm/sm/update-menu']); ?>', obj, function (json)
            {
                $.gritter.add({
                    title: 'Info',
                    text: 'Меню успешно записано'
                });

                mvm.LoadStaticPages();
            }, 'json');
        });

    });
</script>