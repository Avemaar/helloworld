<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\ORM\TableRegistry; 
use Cake\Filesystem\Folder;

use Cake\I18n\I18n;
use Cake\I18n\Time;
use Cake\View\Cell;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link http://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */


/*
*Basic User Authentication system,included with some commonly used functions by other classes
*/


class AppController extends Controller
{

	 /*Finding those Documents with the passed tag_id*/
   public function tagSearch($tagid)
		{
			$this->LoadModel('DocumentsTags');
			if($tagid!=null)
			{
			return $this->DocumentsTags->find('all',['conditions'=>['tag_id'=>$tagid]]);
			}else{return false;}
		}
	
	
	
	

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('Security');`
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();

        $this->loadComponent('RequestHandler');
        $this->loadComponent('Flash');
        $this->loadComponent('Cookie');
		
		 $this->loadComponent('Security');

		
	/*
  *Authentication,Redirect
  */	
		$this->loadComponent('Auth', [
					'authenticate' => [
					'Form' => [
					'fields' => [
					'username' => 'username',
					'password' => 'password']],
					
					
					
					],
					'loginAction' => [
					'controller' => 'Users',
					'action' => 'login'
					],
					
					'loginRedirect' => [
					'controller' => 'Documents',
					'action' => 'index'
					],
					'logoutRedirect' => [
					'controller' => 'Documents',
					'action' => 'index',
					'home'
					],
					
					
					'unauthorizedRedirect' => $this->referer() 
		
		]);
	
  /*permitted actions*/
		$this->Auth->allow(['display']);
			
		$this->Auth->allow([
		'display',
		'tagindex','searchDocument',
		'changeLangHu','changeLangEn'
		
		]);
		
		
		

		

        $this->loadComponent('Security');
        /*Protection against Cross Site Scripting*/
        $this->loadComponent('Csrf');
    }
	


	public function login()
	{
	
			
	/*Setting language through cookie*/
	$lang=$this->Cookie->read('lang');
  $this->set(compact('lang'));
			
	
			if ($this->request->is('post')) {
  
  /*declarete inlogged user*/
    $user = $this->Auth->identify();
     
			   
		   if ($user) {
                $this->Auth->setUser($user);
			
		
			
			$people_query=$this->Users->find('all',
			['conditions'=>['username'=>$this->Auth->user()['username']],
			'contain'=>(['Roles']),
		
			
			])->first();
		/*refresh the last inlogged date*/	
			$people_query->updated=Time::now();

			
			$this->Users->save($people_query);
			$this->Flash->success(__('Succes login'));
			
			
	/*determine the user role*/				   
	$userRoles=$this->Users->find('all',[
	'contain'=>['Roles'],
	'conditions'=>(['id' => $this->Auth->user('id')]),
	]	
	);
	
		foreach($userRoles as $role)
		{
		
		if ((count($role->roles)!==0))
		{
		for($i=0;$i<count($userRoles);$i++)
		{
		switch($role->roles[$i]->name)
		{
		
		/*Set layout menu depends on user's group*/
		case 'admin':
					return $this->redirect($this->Auth->redirectUrl(['controller'=>'Documents','action'=>'index']));
					$this->viewBuilder()->layout('admin_default'); 
					break;
		
    case 'instructor':
					$this->viewBuilder()->layout('instructor_default'); 
					return $this->redirect($this->Auth->redirectUrl(['controller'=>'Documents','action'=>'my-Documents']));			
					break;
		
    case 'student':
					$this->viewBuilder()->layout('student_default'); 
					return $this->redirect($this->Auth->redirectUrl(['controller'=>'PrivateDocuments','action'=>'studentview']));
					break;
		
		default:
		return $this->redirect($this->Auth->redirectUrl(['controller'=>'Documents','action'=>'index-app']));
		
		}
		
			
		}
		}else return $this->redirect($this->Auth->redirectUrl(['controller'=>'Documents','action'=>'index-app'])); 
		}
		  
			
		
			
			
            }
           
			$this->Flash->error(__('Invalid username or password, try again'));
        }
	
	
	
			
	}

	
/*Set the site's language cookie*/	
public function changeLangEn($lang = 'en_US')
{
    $this->Cookie->write('lang', $lang);
    return $this->redirect($this->request->referer());
}
		public function changeLangHu($lang = 'hu_hu')
		{
			$this->Cookie->write('lang', $lang);
			return $this->redirect($this->request->referer());
		}
	
	

	//ezen oldalak engedelyezese
	public function beforeFilter(Event $event)
    {
        //$this->Auth->allow(['password','reset','index', 'view', 'display','tagindex','searchDocument','changeLangHu','changeLangEn']);
    
	
	
			if($this->Auth->user())
			{
				
				$this->set('pers',$this->getPersonId());
				$this->set('islogged',true);
			
			 
			 if($this->getAdminId($this->Auth->user('id'))):
			 
			 $this->set('isadmin',true);
			 else : $this->set('isadmin',false);
			 endif;
			
			if($this->getInstructorId($this->Auth->user('id'))):
			 
			
			 $this->set('isinstructor',true);
			 else : $this->set('isinstructor',false);
			
			
			
			endif;
			
			
			
			}else{$this->set('islogged',false);}
			
			/*Read the site's language cookie*/
			$lang = $this->Cookie->read('lang');
			$this->set('lang',$lang);
			if (empty($lang)) {
				return;
			}

			I18n::locale($lang);
	
	}
	
	
	
    /**
     * Before render callback.
     *
     * @param \Cake\Event\Event $event The beforeRender event.
     * @return \Cake\Network\Response|null|void
     */

	 
	/*Check if the current user is the owner of the Record*/	
	public function isOwnedBy($customeId, $userId)
	{
		return $this->exists(['id' => $costumeId, 'user_id' => $userId]);
	}

	
	 
	 public function fileUpload($uploadData=null)
    {
	
	$this->loadComponent('Auth');
	$this->loadModel('Files');
	
	
	$dir=new Folder();

	if(!is_dir(WWW_ROOT.'uploads'.DS.$this->Auth->user('username').DS))
	{
		$dir=new Folder(WWW_ROOT.'uploads'.DS.$this->Auth->user('username').DS, true, 0755);
		//print  $dir->path;
	}
	else 
		{
		$dir->path=WWW_ROOT.'uploads'.DS.$this->Auth->user('username').DS;
		//print "letezo konyvtar";
		}
	
		
	
        if ($this->request->is('post')) {
            if(!empty($this->request->data['File']['name']))
			{
                
				
				$fileName = $this->request->data['File']['name'];
                $uploadPath = $dir->path;
				
                $uploadFile = $uploadPath.$fileName;
                
				$uploadFilemod = $uploadPath.$this->Auth->user('id').'-'.time().'-'.$fileName;
                
											
				if(move_uploaded_file($this->request->data['File']['tmp_name'],$uploadFilemod))
				{
            	
					
                    
					$uploadData->original_name = $fileName;
					
                    
                    
					$uploadData->path = $uploadPath;
                    
					$uploadData->created = date("Y-m-d H:i:s");
          $uploadData->updated = date("Y-m-d H:i:s");
          $uploadData->user_id = $this->Auth->user('id');
					
        if ($this->Files->save($uploadData)) {
                        
						$this->request->session()->write('file_id', $uploadData->id);
						
						return true;
						
						
						
					}else{
                        return null;
						
                    }
               
				}

				
				
				else{
                    $this->Flash->error(__('Unable to upload file, please try again.'));
                }
            }else{
                $this->Flash->error(__('Please choose a file to upload.'));
            }
            
        }
        $this->set('uploadData', $uploadData);
        

    
	}
	
	public function download($id=null)
	{
		 $this->loadModel('Files');
		 $file=$this->Files->get($id);
		//$file_path = $file->path;
		
		
		
	
		
		if (strpos($file->path, 'uploads') !== false) :
				

		
		$pth=substr($file->path,(strpos($file->path, 'uploads')));
		
		endif;
		
		
		$path=$pth.$file->name;
		
		
		
		
		
		$this->response->file($path, array(
        'download' => true,
        'name' => $file->name,
		));
		
		return $this->response;
	
	}
	
    
	
	public function TagSearchTEMP($id=null){
	if($search!=null)
		{
		
		$query=$this->PublicDocuments->find()
		->matching('Tags',function($q)use($search)
		{return $q->where(['Tags.id'=>$search]);})
		->contain(['Instructors.People'])
		->select(['PublicDocuments.hun_title','PublicDocuments.en_title','PublicDocuments.created','PublicDocuments.updated','PublicDocuments.id',
		'Instructors.id','People.name','People.id']);
		
		
		$publicDocuments = $this->paginate($query);
		}else return false;
		
		}
		  
		
	
	

	 
	 public function isAvaliableDocument($publicDocumentId)
	 {
		$this->LoadModel('DocumentStateTracks');
	 
		if($publicDocumentId!=null)
	 	{ 	 
	  	$query=$this->DocumentStateTracks->find()
		->matching('PublicDocuments',function($q)use($publicDocumentId)
		{return $q
		->where(['private_Document_id IS NOT NULL','PublicDocuments.id'=>$publicDocumentId]);})
		->contain(['PublicDocuments']);
		
		 
		return $query->toArray();
		}
	
	else return false;
	 
	 
	 }

	 public function getAvaliablee($ts=null,$apps=null){
	 $arr=[];
		
		foreach($ts as $t):
		//dump($t->id);
			foreach($apps as $app):
				if($t->id==$app->Documents_sub_detail->id && ($app->state->en_name=='approved'||$app->state->en_name=='completed')):
				 return 1;//$arr[$t->id]=1;
				
			
				break;
				endif;
		endforeach;
		if($t->id!=$app->Documents_sub_detail->id ||($t->id==$app->Documents_sub_detail->id && ($app->state->en_name=='denied'||$app->state->en_name=='pending')))//$arr[$t->id]=0;
		return 0;
		endforeach;
	 
	 
	 }
	 
	
	 
	 public function getUserRole($id=null)
	 {
	$this->loadComponent('Auth');
	$this->loadModel('Users');
		
		

	if($id!=null)
	{
		$userRoles=$this->Users->get($id,
		['contain'=>
		['Roles'=>['fields'=>['Roles.name','UsersRoles.user_id','UsersRoles.role_id']]]
		]);
		
				 	
		$roles=[];
		
		
		foreach($userRoles->roles as $role)
		{	
		$roles[]=$role->name;
		}
		
		
		return $roles;
		}
		
	 
	 } 

	 

	 
	public function getInstructorId($id=null)
	{

	if($id!=null)
	{
	$roles=$this->getUserRole($id);
	
	foreach($roles as $role)
	{
	if($role=='instructor')
		{
			
		$this->loadModel('Users');
	
		$ins=$this->Users->get($id,
		['contain'=>['People.Instructors']]
		);		
		
		
		
		
		if($ins->person->instructor!=null){
		$insid=$ins->person->instructor->id;
		
		
		return $insid;
		
		}}else return false;
	}
	}else return false;

	
	}
	
	
	//Bejelentkezett felhasznaloraol eldonteni hogy Admin -e

	
	public function getAdminId($id=null)
	{
	
	
	
	if($this->Auth->user()!=null && $this->getUserRole($id)!=null)
	{
	$roles=$this->getUserRole($id);
	
	
	
	foreach($roles as $role)
	{
	if($role=='admin')
		{
					
		return $this->Auth->user('username');
	
		}else return false;
		}
	}else{return false;}
	
	}

	
	
	
	
	 
    public function beforeRender(Event $event)
    {
	$this->loadComponent('Auth');
	$this->loadModel('Users');
		
		
	

		
		//$this->setMenu($this->Auth->User('id'));
		$this->setAdminInstructor($this->Auth->User('id'));
		
		
		

	
        if (!array_key_exists('_serialize', $this->viewVars) &&
            in_array($this->response->type(), ['application/json', 'application/xml'])
        ) {
           
			$this->set('_serialize', true);
        }
    }
	

		
		
	public function setAdminInstructor($id)
		{
		if($id!=null)
		{
			$group=$this->getUserRole($id);
			
			foreach($group as $gr)
			{
			if($gr=='admin'){
			$isadmin=true;
			$this->set('isadmin',$isadmin);
			}
			}
		}
		}
	
		public function searchPeople()
	{
	
	  $this->loadModel('People');
		if ($this->request->is('post'))
   {
		
      if(!empty($this->request->data) && isset($this->request->data) )
      {
         //remove unwanted spaces
         $search_key = trim($this->request->data['input']);
		
		
		$conditions[] = array(
         "OR" => array(
           
            "People.name LIKE" => "%".$search_key."%",
            "People.email LIKE" => "%".$search_key."%",
            "People.neptunid LIKE" => "%".$search_key."%",
            "People.mothername LIKE" => "%".$search_key."%",
            
            
			
            ));

		/*this query prevent sql injection*/
		$people=$this->People->find('all',[
		'conditions'=>$conditions,
		'contain'=>[
				
		],
		
		]
		);
		
		
		$this->set('people', $people);
		$usersRoles = $this->paginate($people);
		
		
		
		}}
	
}	
	
	
	
	
	
	
}
