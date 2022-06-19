<?php


namespace App;

class Validator implements ValidatorInterface
{
    public function validate(array $item)
    {
        $errors = [];
        if (strlen($item['nickname']) <= 4) {
            $errors['nickname'] = "Nickname must be greater than 4 characters";
        }
        if ($item['email'] === '') {
            $errors['email'] = "Can't be blank";
        }
        return $errors;
    }
}