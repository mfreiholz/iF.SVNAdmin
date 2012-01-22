<?php
class IF_ACLModule
{
  private $name = NULL;

  public function __construct($name)
  {
    $this->name = $name;
  }

  public function getName()
  {
    return $this->name;
  }
}
?>