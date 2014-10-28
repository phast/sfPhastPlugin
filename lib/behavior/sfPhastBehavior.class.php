<?php


class sfPhastBehavior extends SfPropelBehaviorBase
{


    public function modifyDatabase()
    {
        foreach ($this->getDatabase()->getTables() as $table) {
            switch ($table->getName()) {
                case 'widget':
                    $table->setBaseClass('PhastWidget');
                    break;
                case 'user':
                    $table->setBaseClass('PhastUser');
                    break;
                case 'user_sign':
                    $table->setBaseClass('PhastUserSign');
                    break;
                case 'user_session':
                    $table->setBaseClass('PhastUserSession');
                    break;
                case 'user_group':
                    $table->setBaseClass('PhastUserGroup');
                    break;
                case 'user_group_section':
                    $table->setBaseClass('PhastUserGroupSection');
                    break;
                case 'mailing_schedule':
                    $table->setBaseClass('PhastMailingSchedule');
                    break;
                case 'mailing_broadcast':
                    $table->setBaseClass('PhastMailingBroadcast');
                    break;
                case 'image':
                    $table->setBaseClass('PhastImage');
                    break;
                case 'page':
                    $table->setBaseClass('PhastPage');
                    break;
                case 'holder':
                    $table->setBaseClass('PhastHolder');
                    foreach ($table->getForeignTableNames() as $rel) {
                        $this
                            ->getDatabase()
                            ->getTable($rel)
                            ->setDescription('~holder');
                    }
                    break;

            }
        }

        parent::modifyDatabase();
    }

    public function preSave()
    {
        if ($this->isDisabled()) {
            return;
        }

        return '';
    }

    public function preInsert()
    {
        if ($this->isDisabled()) {
            return;
        }

        return '';
    }


    public function objectMethods()
    {
        if ($this->isDisabled()) {
            return;
        }

        $script = '';

        $imageColumns = [];
        foreach ($this->getTable()->getColumns() as $column) {
            if (preg_match('/^(\w+)?ImageId$/', $column->getPhpName(), $match)) {
                $prefix = isset($match[1]) ? $match[1] : '';
                $imageColumns[] = [
                    'column' => $column,
                    'prefix' => $prefix
                ];
            }
        }

        foreach ($imageColumns as $column) {
            $prefix = $column['prefix'];
            $column = $column['column'];
            $method = count($imageColumns) > 1 ? "getImageRelatedBy{$column->getPhpName()}" : 'getImage';
            $script .= "public function get{$prefix}ImageTag(\$width = null, \$height = null, \$scale = null, \$inflate = null){
                        if(\$image = \$this->{$method}()){
                            return \$image->getTag(\$width, \$height, \$scale, \$inflate);
                        }else{
                            return '';
                        }
                    }";
            $script .= "public function get{$prefix}ImageUri(\$width = null, \$height = null, \$scale = null, \$inflate = null){
                        if(\$image = \$this->{$method}()){
                            return \$image->getUri(\$width, \$height, \$scale, \$inflate);
                        }else{
                            return '';
                        }
                    }";
            $script .= "public function upload{$prefix}Image(\$request, \$uploadPath){
                        \$upload = new sfPhastUpload(\$request);
                        \$upload->path(sfConfig::get('sf_upload_dir') . \$uploadPath);
                        \$upload->type('web_images');
                        \$upload->save();
                        if (\$this->get{$prefix}ImageId()) {
                            \$this->{$method}()->updateFromUpload(\$upload);
                        } else {
                            \$this->set{$prefix}ImageId(Image::createFromUpload(\$upload)->getId());
                        }
                    }";
        }


        return $script;

    }

}
