<?php
/*********************************************************************
    class.equipment_category.php

    Backend support for equipment categories.

    Copyright (c)  2013 XpressTek
    http://www.xpresstek.net

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class Equipment_Category {
    var $id;
    var $ht;

    function Equipment_Category($id) {
        $this->id=0;
        $this->load($id);
    }

    function load($id) {

        $sql=' SELECT cat.*,count(equipment.equipment_id) as equipments '
            .' FROM '.EQUIPMENT_CATEGORY_TABLE.' cat '
            .' LEFT JOIN '.EQUIPMENT_TABLE.' equipment ON(equipment.category_id=cat.category_id) '
            .' WHERE cat.category_id='.db_input($id)
            .' GROUP BY cat.category_id';

        if (!($res=db_query($sql)) || !db_num_rows($res)) 
            return false;

        $this->ht = db_fetch_array($res);
        $this->id = $this->ht['category_id'];

        return true;
    }

    function reload() {
        return $this->load($this->getId());
    }

    /* ------------------> Getter methods <--------------------- */
    function getId() { return $this->id; }
    function getName() { return $this->ht['name']; }
    function getNumEquipment() { return  $this->ht['equipments']; }
    function getDescription() { return $this->ht['description']; }
    function getNotes() { return $this->ht['notes']; }
    function getCreateDate() { return $this->ht['created']; }
    function getUpdateDate() { return $this->ht['updated']; }

    function isPublic() { return ($this->ht['ispublic']); }
    function getHashtable() { return $this->ht; }
    
    /* ------------------> Setter methods <--------------------- */
    function setName($name) { $this->ht['name']=$name; }
    function setNotes($notes) { $this->ht['notes']=$notes; }
    function setDescription($desc) { $this->ht['description']=$desc; }

    /* --------------> Database access methods <---------------- */
    function update($vars, &$errors) { 

        if(!$this->save($this->getId(), $vars, $errors))
            return false;

        //TODO: move FAQs if requested.

        $this->reload();

        return true;
    }

    function delete() {

        $sql='DELETE FROM '.EQUIPMENT_CATEGORY_TABLE
            .' WHERE category_id='.db_input($this->getId())
            .' LIMIT 1';
        if(db_query($sql) && ($num=db_affected_rows())) {
            db_query('DELETE FROM '.EQUIPMENT_TABLE
                    .' WHERE category_id='.db_input($this->getId()));
    
        }

        return $num;
    }

    /* ------------------> Static methods <--------------------- */

    function lookup($id) {
        return ($id && is_numeric($id) && ($c = new Equipment_Category($id)))?$c:null;
    }

    function findIdByName($name) {
        $sql='SELECT category_id FROM '.EQUIPMENT_CATEGORY_TABLE.' WHERE name='.db_input($name);
        list($id) = db_fetch_row(db_query($sql));

        return $id;
    }

    function findByName($name) {
        if(($id=self::findIdByName($name)))
            return new Equipment_Category($id);

        return false;
    }

    function validate($vars, &$errors) {
         return self::save(0, $vars, $errors,true);
    }

    function create($vars, &$errors) {
        return self::save(0, $vars, $errors);
    }

    function save($id, $vars, &$errors, $validation=false) {

        //Cleanup.
        $vars['name']=Format::striptags(trim($vars['name']));
      
        //validate
        if($id && $id!=$vars['id'])
            $errors['err']='Internal error. Try again';
      
        if(!$vars['name'])
            $errors['name']='Category name is required';
        elseif(strlen($vars['name'])<3)
            $errors['name']='Name is too short. 3 chars minimum';
        elseif(($cid=self::findIdByName($vars['name'])) && $cid!=$id)
            $errors['name']='Category already exists';

        if(!$vars['description'])
            $errors['description']='Category description is required';

        if($errors) return false;

        /* validation only */
        if($validation) return true;

        //save
        $sql=' updated=NOW() '.
             ',ispublic='.db_input(isset($vars['ispublic'])?$vars['ispublic']:0).
             ',name='.db_input($vars['name']).
             ',description='.db_input(Format::safe_html($vars['description'])).
             ',notes='.db_input($vars['notes']);

        if($id) {
            $sql='UPDATE '.EQUIPMENT_CATEGORY_TABLE.' SET '.$sql.' WHERE category_id='.db_input($id);
            if(db_query($sql))
                return true;

            $errors['err']='Unable to update Equipment category.';

        } else {
            $sql='INSERT INTO '.EQUIPMENT_CATEGORY_TABLE.' SET '.$sql.',created=NOW()';
            if(db_query($sql) && ($id=db_insert_id()))
                return $id;

            $errors['err']='Unable to create Equipment category. Internal error';
        }

        return false;
    }
}
?>
