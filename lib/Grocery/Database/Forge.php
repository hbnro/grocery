<?php

namespace Grocery\Database;

class Forge extends \Grocery\Handle\Table
{

  public function scheme()
  {
    return \Grocery\Helpers::map($this, function ($obj) {
        return array(
          'columns' => $obj->columns(),
          'indexes' => $obj->indexes(),
        );
      });
  }

  public function reset()
  {
    foreach ($this->tables() as $one) {
      $this->drop($one);
    }

    return $this;
  }

}
