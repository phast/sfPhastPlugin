<?php

class PhastUserGroup extends BaseObject{

    public function getCompiledCondition(){
        $expression = new Symfony\Component\ExpressionLanguage\ExpressionLanguage();
        return $this->condition ? $expression->compile($this->condition, ['user', 'group']) : '';
    }

    public function evaluateCondition($user){
        $expression = new Symfony\Component\ExpressionLanguage\ExpressionLanguage();
        return $this->condition ? $expression->evaluate($this->condition, ['user' => $user, 'group' => $this]) : false;
    }

}
