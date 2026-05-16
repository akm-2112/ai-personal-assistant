<?php

namespace Modules\CvWriter\Enums;

enum CategoryType : string {
    case SKILLS = 'skills';
    case EXPERIENCE = 'experience';
    case PROJECTS = 'projects';
    case BIO = 'bio';

    public static function default() :self 
     {
        return self::BIO;
     }

}

