<?php

namespace App\Service;

use App\Entity\Setting;
use Doctrine\Common\Persistence\ManagerRegistry;

class UserHelper
{
    public function __construct(ManagerRegistry $mr)
    {
        $this->mr = $mr;
    }

    public function isUserCreationAllowed()
    {
        $setting = $this
            ->mr
            ->getRepository(Setting::class)
            ->getUserCreationAllowed();
        $entityManager = $this->mr->getManager();

        if (is_null($setting)) {
            $setting = new Setting();
            $setting->setCode('user_creation');
            $setting->setVal(1);
            $entityManager->persist($setting);
            $entityManager->flush();
            $allowed = true;
        } else {
            $allowed = $setting->getVal() == 1;
        }

        return $allowed;
    }
}