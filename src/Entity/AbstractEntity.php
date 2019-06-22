<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\HasLifecycleCallbacks
 */
 abstract  class AbstractEntity
{

     /**
      * @ORM\PrePersist
      */
     public function setCreatedAtPrePersist(){
         if($this->getCreatedAt()==null){
             $this->setCreatedAt(new \DateTime('now'));
         }
         $this->setUpdatedAt(new \DateTime('now'));
     }

     /**
      * @ORM\PreUpdate
      */
     public function setUpdatedAtPreUpdate(){

         $this->setUpdatedAt(new \DateTime('now'));
     }
}
