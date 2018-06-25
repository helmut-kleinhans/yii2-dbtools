<?php
use yii\helpers\Html;
use yii\bootstrap\Nav;
use common\widgets\Alert;

$this->beginPage() 

?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <script  src="https://code.jquery.com/jquery-3.3.1.slim.js"
			 integrity="sha256-fNXJFIlca05BIO2Y5zh1xrShK3ME+/lYZ0j+ChxX2DA="
			 crossorigin="anonymous"></script>
			 
    <script async="true" src="https://use.edgefonts.net/source-code-pro.js"></script>			 
	<link rel=stylesheet href="https://unpkg.com/ace-diff@^2.0.0/dist/ace-diff.min.css">
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>    
</head>
<body>
<div class="acediff"></div>
<?= $content ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.1.8/ace.js" type="text/javascript" charset="utf-8"></script>
<script src="https://unpkg.com/ace-diff@^2.0.0"  type="text/javascript" charset="utf-8"></script>
<script>
	var aceDiffer = null;
    $(document).ready(function () {

		aceDiffer = new AceDiff({
			mode:"ace/mode/mysql",
			theme: "ace/theme/solarized_dark",
			diffGranularity: "specific",
			element: '.acediff',
			left: {
				content: "",
			},
			right: {
				content: "",
			},
		});
		
		aceDiffer.getEditors().left.setOptions( {enableBasicAutocompletion: true} ); 
    });
    
    
   function loadData(data) {

		if(aceDiffer){
			var edis = aceDiffer.getEditors();
			if(data.left)
				edis.left.setValue(data.left,-1);
			else
				edis.left.setValue('',-1);

			if(data.right)
				edis.right.setValue(data.right,-1);
			else
				edis.right.setValue('',-1);
		}
    } 
    
    function getData(){
		var data = { 
			left:"",
			right:""
		}
		var edis = aceDiffer.getEditors();
		data.left = edis.left.getValue();	
		data.right = edis.right.getValue();	
		return data;	
	}   

</script>
</body>
</html>
