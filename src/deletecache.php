<?php

trait deletecache
{

    public function deletecache()
    {
        foreach([ CAPSULES_TASKS_LOCATION, CAPSULES_PARTIES_LOCATION, OWNER_DETAILS_LOCATION, CAPSULES_NOTES_LOCATION] as $path){
            $this->unlinkFolder($path);
            echo '>>>>> >>>>> Cleared '.$path.PHP_EOL;
        }

    }

    private function unlinkFolder($location){
        $files = scandir($location, 1);
        if(!is_array($files)){
            return;
        }
        foreach ($files as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }
            if(unlink(CAPSULES_TASKS_LOCATION . $file)){
                echo $file.' - Deleted. '.PHP_EOL;
            }
        }

    }


}