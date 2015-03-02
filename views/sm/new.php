<?php

    use yii\helpers\Html;
    use yii\widgets\ActiveForm;
    use kyra\steditor;

    $x = Yii::$app->session->getFlash('page.success');

    $disabled = isset($model->CanUserDelete) && $model->CanUserDelete == 0
                ? ['disabled' => 'disabled']
                : [];



    $form = ActiveForm::begin([
            'id' => 'edit-page',
            'enableClientValidation' => false,
            'enableAjaxValidation' => true,
            'fieldConfig' => [
                    'template' => '{label}<div class="col-sm-10">{input}</div><div class="col-sm-10">{error}</div>',
                    'labelOptions' => ['class' => 'col-sm-2 control-label'],
            ],
            'options' => ['class' => 'form-horizontal'],
    ]) ?>


<?php if (!empty($model->PageID)) echo $form->field($model, 'PageID', ['template' => '{input}'])->hiddenInput(); ?>
<?= $form->field($model, 'Title')->textInput() ?>
<?= $form->field($model, 'MenuTitle')->textInput() ?>
<?= $form->field($model, 'UrlKey')->textInput($disabled) ?>
<?=
    $form->field($model, 'ContentJSON',
            ['template' => '{error}<div class="col-sm-12">{input}</div>'])->widget('kyra\steditor\StEditor'); ?>

    <div class="form-group">
        <div class="col-sm-offset-2">
            <?= Html::submitButton('Записать', ['class' => 'btn btn-primary']) ?>
        </div>
    </div>


<?php ActiveForm::end() ?>

