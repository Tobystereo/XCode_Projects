<?php
/**
 * @file
 * Multicurl
 */
?><?php
/*

Licence:
==============================================================================
(c) 2011, Kaldor Holdings Ltd
This module is released under the GNU General Public License.
See COPYRIGHT.txt and LICENSE.txt

 */?><?php

class MultiCurl
{
    private $allToDo;
    private $entries;
    private $filehandles;
    private $multiHandle;
    
    private $failures = array();
    
    private $warningFileSize = 0;
    private $maxConcurrent = 2;
    private $currentIndex  = 0;
    private $processedCount = 0;

    private $info          = array();
    private $options;


    public function __construct($entries, $concurrent, $warning_file_size)
    {
    

        $this->options = array(
          CURLOPT_RETURNTRANSFER => true,
           CURLOPT_USERAGENT => "PugpigNetwork/Packager",

           CURLOPT_MAXREDIRS      => 3,
           // CURLOPT_HEADER         => true, // response headers
           // CURLINFO_HEADER_OUT    => true, // request headers
           // CURLOPT_PROXY          => '127.0.0.1:9999', // Good for debugging
           CURLOPT_TIMEOUT        => PUGPIG_CURL_TIMEOUT
        );

        // We can't follow locations if certain settings are set
        if ((!ini_get('open_basedir') && !ini_get('safe_mode'))) {
          $this->options[CURLOPT_FOLLOWLOCATION] = true;
        }

        $this->entries = $entries;

        $this->allToDo = array_keys($this->entries);
        $this->filehandles = array();
        $this->maxConcurrent = $concurrent;
        $this->warningFileSize = $warning_file_size;
        $this->multiHandle = curl_multi_init();
    }

    public function getFailures() {
      return $this->failures;
    }


    public function process()
    {
        $running = 0;
        do {
            $this->_addHandles(min(array($this->maxConcurrent - $running, $this->_moreToDo())));
            while ($exec = curl_multi_exec($this->multiHandle, $running) === -1) {
            }
            
            // _print_immediately('Info Count: ' . count($this->info) . '<br />');
            curl_multi_select($this->multiHandle);
            while ($multiInfo = curl_multi_info_read($this->multiHandle, $msgs)) {
                $this->_processData($multiInfo);
                    
                // Clean up the handle
                curl_multi_remove_handle($this->multiHandle, $multiInfo['handle']);
                curl_close($multiInfo['handle']);

            }

        } while ($running || $this->_moreTodo());
        return $this;
    }    

    private function _addHandles($num)
    {
        while ($num > 0) {
            // Get the URL and file name, and create the directory if it doesn't exist
            $url = $this->allToDo[$this->currentIndex];
            $name = $this->entries[$url];
            $dir = dirname($name);
            if (!file_exists($dir)) mkdir($dir, 0777, TRUE);

            // If the file exists, we don't need to add it
            if (file_exists($name)) {
              $extension = '';
              $path_parts = pathinfo($name);
              if (isset($path_parts['extension'])) {
                $extension = $path_parts['extension'];
              } 
              $char = pugpig_get_download_char($extension, 'EXT');

              _print_immediately('<a class="skip" href="'.$url .'" target="_blank" title="Skipped: '.$url . ' ">'. $char.'</a>');
              $this->processedCount++;
              if ($this->processedCount%100 == 0 || $this->processedCount == count($this->entries)) _print_immediately("<br />");
            } else {
              $handle = curl_init($url);
              curl_setopt_array($handle, $this->options);
 
              $this->filehandles[$url]=fopen ($name, "w");
            
              // print_r("Opened handle for ".$url.": " . $this->filehandles[$url] . "<br />");

              curl_setopt ($handle, CURLOPT_FILE, $this->filehandles[$url]);
              curl_multi_add_handle($this->multiHandle, $handle);
              $this->info[(string) $handle]['url'] = $this->allToDo[$this->currentIndex];
              $num--;
            }
            $this->currentIndex++;
            //_print_immediately("c:" . $this->currentIndex . "(".$num.")<br />");
            if ($this->currentIndex >= count($this->allToDo)) break;
        }
       
    }        

    private function _moreToDo()
    {
        return count($this->allToDo) - $this->currentIndex;
    }

    private function _processData($multiInfo)
    {
        $handleString = (string) $multiInfo['handle'];
        $this->info[$handleString]['multi'] = $multiInfo;
        $this->info[$handleString]['curl']  = curl_getinfo($multiInfo['handle']);
        
        $http_url = $this->info[$handleString]['url'];
        $http_code = $this->info[$handleString]['curl']['http_code'];
        $content_type = $this->info[$handleString]['curl']['content_type'];
        $content_length = $this->info[$handleString]['curl']['download_content_length'];
        $total_time = $this->info[$handleString]['curl']['total_time'];
        $starttransfer_time = $this->info[$handleString]['curl']['starttransfer_time'];
        $connect_time = $this->info[$handleString]['curl']['connect_time'];
        
        // $request_header = $this->info[$multiInfo['handle']]['curl']['request_header'];
        // print_r($this->info[$multiInfo['handle']]['curl']);
        
        // Close the file we're downloading into
        fclose ($this->filehandles[$http_url]);

  $file_exists = file_exists($this->entries[$http_url]);
  $file_size = $file_exists ? filesize($this->entries[$http_url]) : 0;

        $color = 'pass';

        if ($http_code != 200) {  
          $color = 'fail';
          $this->failures[$http_url] = "HTTP Error after " . $total_time . " seconds: " . $http_code;
          if ($http_code == 0) $this->failures[$http_url] .= " (possibly too many concurrent connections).";
          unlink($this->entries[$http_url]);
        } else {
          if (!$file_exists) {
            $color = 'fail';
            $this->failures[$http_url] = "Unable to save file after download. Maybe file name is too long?";
          } elseif ($file_size == 0) {
            // Delete it so that we have to download it again if the user refreshes
            unlink($this->entries[$http_url]);
            $color = 'fail';
            $this->failures[$http_url] = "The file is $file_size bytes in length.";
          }
          if ($content_length > $this->warningFileSize * 2) {
            $color = 'bigwarning';
          } elseif ($content_length > $this->warningFileSize) {
            $color = 'warning';
          }
          
          if ($total_time > 10) {
            $color = 'veryslowwarning';
          } elseif ($total_time > 5) {
            $color = 'slowwarning';
          }
        }        
        
        $char = pugpig_get_download_char($content_type, 'MIME');

        _print_immediately('<a class="'.$color.'" href="'.$http_url .'" target="_blank" title="'.$http_url . 
         ' [Response: '.$http_code.', Size: '.$content_length.' ('. bytesToSize($file_size).'), Type: ' . $content_type . 
        ', Time: '. $total_time .', TTFB: '.$starttransfer_time.', Connect Time: ' . $connect_time . '] ">'.$char.'</a>');

        $this->processedCount++;
        if ($this->processedCount%100 == 0 || $this->processedCount == count($this->entries)) _print_immediately("<br />");
        
    }
  }

 
     function pugpig_get_download_char($type, $method='MIME') {
      $char = '*';
      $type = strtolower($type);
      
      if ($method == 'MIME') {
        if (startsWith($type, 'application/atom+xml')) $char = 'a';
        if (startsWith($type, 'application/pugpigpkg+xml')) $char = 'p';
        if (startsWith($type, 'application/xml')) $char = 'x';
        if (startsWith($type, 'text/xml')) $char = 'x';
        if (startsWith($type, 'text/html')) $char = 'h';
        if (startsWith($type, 'text/plain')) $char = 't';
        if (startsWith($type, 'text/cache-manifest')) $char = 'm';
        if (startsWith($type, 'application/x-font')) $char = 'f';
        if (startsWith($type, 'image')) $char = 'i';
        if (startsWith($type, 'text/css')) $char = 'c';
        if (startsWith($type, 'application/javascript')) $char = 'j';
        if (startsWith($type, 'application/x-javascript')) $char = 'j';
      }
    
      if ($method == 'EXT') {
        if (startsWith($type, 'xml')) $char = 'x';
        if (startsWith($type, 'manifest')) $char = 'm';
        if (startsWith($type, 'html')) $char = 'h';
        if (startsWith($type, 'ttf')) $char = 'f';
        if (startsWith($type, 'otf')) $char = 'f';
        if (startsWith($type, 'txt')) $char = 't';
        if (startsWith($type, 'jpg')) $char = 'i';
        if (startsWith($type, 'jpeg')) $char = 'i';
        if (startsWith($type, 'png')) $char = 'i';
        if (startsWith($type, 'gif')) $char = 'i';
        if (startsWith($type, 'css')) $char = 'c';
        if (startsWith($type, 'js')) $char = 'j';
      }    
      
      return $char;
    
    }   
    
  
