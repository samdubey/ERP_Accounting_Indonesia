<?php

class GBssController extends Controller
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/column2left';

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
				'rights',
		);
	}

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionRecruitmentView($id)
	{
		$files = $this->read_folder_directory (Yii::app()->basePath."/../images/recruitment/".$id);
		
		$newRecruitment=$this->newRecruitment($id);
		
		$this->render('view',array(
				'model'=>$this->loadModel($id),
				'modelRecruitment'=>$newRecruitment,
				'files'=>$files,
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function newRecruitment($id)
	{
		$model=new gSelectionProgress;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['gSelectionProgress']))
		{
			$model->attributes=$_POST['gSelectionProgress'];
			$model->parent_id=$id;
			if($model->save())
				$this->redirect(array('view','id'=>$id));
		}

		return $model;
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$model=new gSelection;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['gSelection']))
		{
			$model->attributes=$_POST['gSelection'];
			$model->final_result_id=1;

			$model->image=CUploadedFile::getInstance($model,'image');
			$docs=CUploadedFile::getInstancesByName('docs');

			if (isset($model->image))
				$model->photo_path=$model->image->name;

			if($model->save()) {
				if (isset($model->image))
					$model->image->saveAs(Yii::app()->basePath . '/../shareimages/hr/recruitment/'.$model->image->name);

				if (isset($docs)) {
					mkdir(Yii::getPathOfAlias('webroot').'/shareimages/hr/recruitment/'.$model->id);
					//chmod(Yii::getPathOfAlias('webroot').'/shareimages/hr/recruitment/'.$model->id, 0755);

					foreach ($docs as $image => $pic) {
						$pic->saveAs(Yii::app()->basePath . '/../shareimages/hr/recruitment/'.$model->id . '/'.$pic->name);
					}
				}
					
					
				$this->redirect(array('/m1/gSelection'));
			}
		}


		$this->render('/gSelection/create',array(
				'model'=>$model,
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
		$model=$this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['gSelection']))
		{
			$model->attributes=$_POST['gSelection'];
			if($model->save())
				$this->redirect(array('view','id'=>$model->id));
		}

		$this->render('/gSelection/update',array(
				'model'=>$model,
		));
	}


	public function actionRecruitmentUpdateAjax()
	{
		$model->attributes = $_POST;
		$model=$this->loadModel($_POST['pk']);
		$model->$_POST['name']=$_POST['value'] ;
		$model->final_result_id=2 ;
		if($model->save()) {
			return true;
		} else
			return false;
		
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		if(Yii::app()->request->isPostRequest)
		{
			// we only allow deletion via POST request
			$this->loadModel($id)->delete();

			// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
			if(!isset($_GET['ajax']))
				$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
		}
		else
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
	}

	/**
	 * Lists all models.
	 */
	public function actionRecruitment()
	{
		$model=new gSelection('search');

		$this->render('recruitment',array(
			'model'=>$model,
		));
	}

	public function actionIndex()
	{
		$this->render('index',array(
		));
	}

	/**
	 * Lists all models.
	 */
	public function actionList()
	{
		$model=new gSelection('search');
		$model->unsetAttributes();

		$criteria=new CDbCriteria;
		$criteria1=new CDbCriteria;

		if(isset($_GET['gSelection'])) {
			$model->attributes=$_GET['gSelection'];

			$criteria1->compare('candidate_name',$_GET['gSelection']['candidate_name'],true,'OR');
			$criteria1->compare('for_position',$_GET['gSelection']['candidate_name'],true,'OR');
		}

		$criteria->mergeWith($criteria1);
		$criteria->compare('company_id',sUser::model()->getGroup());
		
		$dataProvider=new CActiveDataProvider('gSelection', array(
				'criteria'=>$criteria,
		)
		);

		$this->render('list',array(
				'dataProvider'=>$dataProvider,
				'model'=>$model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer the ID of the model to be loaded
	 */
	public function loadModel($id)
	{
		$model=gSelection::model()->findByPk($id,'company_id = '.sUser::model()->getGroup());
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='g-recruitment-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	public function actionRecruitAutoComplete()
	{
		$res =array();
		if (isset($_GET['term'])) {
			$path=Yii::app()->homeUrl."/..";
			$qtxt ="SELECT candidate_name as label, DATE_FORMAT(birthdate,'%d-%m-%Y') as bdate, id, CONCAT('".$path."/images/recruitment/',photo_path) as ppath FROM g_recruitment WHERE candidate_name LIKE :name ORDER BY candidate_name LIMIT 5";
			$command =Yii::app()->db->createCommand($qtxt);
			$command->bindValue(":name", '%'.$_GET['term'].'%', PDO::PARAM_STR);
			//$res =$command->queryColumn();
			$res =$command->queryAll();

		}
		echo CJSON::encode($res);
	}

	private function read_folder_directory($dir = "root_dir/dir")
	{
		$listDir = array();
		if (is_dir($dir)) {
			if($handler = opendir($dir)) {
				while (($sub = readdir($handler)) !== FALSE) {
					if(is_file($dir."/".$sub)) {
						$listDir[] = $sub;
					}
				}
				closedir($handler);
			}
		}

		return $listDir;
	}
	
	public function actionDeptUpdate() {
		$cat_id = $_POST['gSelection']['company_id'];
		$models=aOrganization::model()->findAll(array('condition'=>'parent_id = '.$cat_id,'order'=>'id'));

		foreach($models as $model) {
			foreach($model->childs as $mod)
				foreach($mod->childs as $m)
				//$_items[$m->getparent->getparent->name ." - ". $m->getparent->name][$m->id]=$m->name;
				$_items[$m->id]=$m->name;
		}

		//$data=CHtml::listData($models,'id','name');

		foreach($_items as $value=>$dept)  {
			echo CHtml::tag('option',
					array('value'=>$value),CHtml::encode($dept),true);
		}
	}
	
	public function actionCandidateAutoComplete()
	{
		$res =array();
		if (isset($_GET['term'])) {

			$qtxt ="SELECT a.candidate_name FROM g_recruitment a
			WHERE company_id = ".sUser::model()->getGroup()." AND
			a.candidate_name LIKE :name
			ORDER BY a.candidate_name LIMIT 20";
			
			$command =Yii::app()->db->createCommand($qtxt);
				$command->bindValue(":name", '%'.$_GET['term'].'%', PDO::PARAM_STR);
				$res =$command->queryColumn();
				//$res =$command->queryAll();

		}
		echo CJSON::encode($res);
	}

	public function actionReport()
	{
		$model=new fBeginEndDate;
	
		if(isset($_POST['fBeginEndDate']))
		{
			$model->attributes=$_POST['fBeginEndDate'];
			if($model->validate()) {

				if ($model->report_id ==1 ) {  //Detail
					$pdf=new recruitment1('P','mm','A4');
					$pdf->AliasNbPages();
					$pdf->AddPage();
					$pdf->SetFont('Arial','',12);
					
					$criteria=new CDbCriteria;
					$criteria->addBetweenCondition('input_date',date("Y-m-d",strtotime($model->begindate)),date("Y-m-d",strtotime($model->enddate)));
					$models=gSelection::model()->findAll($criteria);
					
					if($models===null)
						throw new CHttpException(404,'Record not found.');
						
					$pdf->report($models,$model->begindate,$model->enddate);		
				} elseif ($model->report_id ==2) {  //rekap psikotest
					$pdf=new recruitment2('P','mm','A4');
					$pdf->AliasNbPages();
					$pdf->AddPage();
					$pdf->SetFont('Arial','',12);
					
					$models=gSelection::model()->reportPsikotest($model->begindate,$model->enddate);
					
					if($models===null)
						throw new CHttpException(404,'Record not found.');
						
					$pdf->report($models,$model->begindate,$model->enddate);		
				} elseif ($model->report_id ==3) {  //Rekap interviewHr
					$pdf=new recruitment3('P','mm','A4');
					$pdf->AliasNbPages();
					$pdf->AddPage();
					$pdf->SetFont('Arial','',12);
					
					$models=gSelection::model()->reportInterviewHr($model->begindate,$model->enddate);
					
					if($models===null)
						throw new CHttpException(404,'Record not found.');
						
					$pdf->report($models,$model->begindate,$model->enddate);		
				} elseif ($model->report_id ==4) {  //Rekap interviewUser
					$pdf=new recruitment4('P','mm','A4');
					$pdf->AliasNbPages();
					$pdf->AddPage();
					$pdf->SetFont('Arial','',12);
					
					$models=gSelection::model()->reportInterviewUser($model->begindate,$model->enddate);
					
					if($models===null)
						throw new CHttpException(404,'Record not found.');
						
					$pdf->report($models,$model->begindate,$model->enddate);		
				} elseif ($model->report_id ==5) {  //Rekap Source
					$pdf=new recruitment5('P','mm','A4');
					$pdf->AliasNbPages();
					$pdf->AddPage();
					$pdf->SetFont('Arial','',12);
					
					$models=gSelection::model()->reportSource($model->begindate,$model->enddate);
					
					if($models===null)
						throw new CHttpException(404,'Record not found.');
						
					$pdf->report($models,$model->begindate,$model->enddate);		
				}

				$pdf->Output();
			}
		}

		$this->render('/gSelection/report',array('model'=>$model));		
	}

	public function actionUpload($id) {
        Yii::import("ext.EAjaxUpload.qqFileUploader");
 
        $folder='sharedocs/recruitmentdocuments/'.$id."/";  // folder for uploaded files
        $allowedExtensions = array("jpg");  //array("jpg","jpeg","gif","exe","mov" and etc...
        //$sizeLimit = 5 * 1024 * 1024;// maximum file size in bytes
        $sizeLimit = 500 * 1024 ;// maximum file size in bytes
        $uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
        $result = $uploader->handleUpload($folder);
        $return = htmlspecialchars(json_encode($result), ENT_NOQUOTES);

        $fileSize=filesize($folder.$result['filename']);//GETTING FILE SIZE
        $fileName=$result['filename'];//GETTING FILE NAME
  
  		gSelection::model()->updateByPk($id,array('photo_path'=>$fileName,'updated_date'=>time(),'updated_by'=>Yii::app()->user->id));
		echo $return;// it's array
	}	
	
	
}
