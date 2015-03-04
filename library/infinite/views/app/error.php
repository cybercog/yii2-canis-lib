<?php
/**
 * ./app/views/app/error.php.
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 */

/* @var $this SiteController */
/* @var $error array */

$this->pageTitle = Yii::$app->name.' - Error';
$this->breadcrumbs = [
    'Error',
];
?>

<h2>Error <?php echo $code; ?></h2>

<div class="error">
<?php echo CHtml::encode($message); ?>
</div>
