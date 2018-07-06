<?php
namespace App\View\Helper;

use Cake\View\Helper;
use Cake\View\View;


class SignHelper extends Helper {
    
    var $helpers=array('Html');
    
    
 public function setSign($temp=null)
         {
   
           
        if($temp==1)
		
         {echo $this->Html->image('test-pass-icon.png');
         }else
		 {echo $this->Html->image('test-fail-icon.png');}
     
     
         }
}
       



?>