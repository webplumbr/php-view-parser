<?php
namespace View;

/**
 * This Class was inspired by the simple XTemplate (www.phpxtemplate.org)
 * 
 * Cache Manager has been implemented to enable caching of parsed templates
 * 
 */

class Parser
{
  protected $file;
  protected $vars;
  protected $blocks;
  protected $parsedBlocks;
  protected $cacheManager;
  protected $isCached;
  
  protected $genericNodePattern = '/<!--\s+BEGIN\s*:\s*([a-zA-Z0-9_]+)\s*-->(.*?)<!--\s+END\s*:\s*\1\s*-->/s';
  protected $lookupNodePattern  = '/(<!--\s+BEGIN\s*:\s*LOOKUP_NODE\s*-->.*?<!--\s+END\s*:\s*LOOKUP_NODE\s*-->)/s';
  protected $varsPattern        = '/\{([a-zA-Z0-9_\.]+)\}/s';
  protected $fileLookupPattern  = '/\{%FILE%\s*([^\}]+)\}/s';
  
  protected $tree = array();
  protected $parent = null;
  
  protected $basePath = ''; /* set this to your project root folder with trailing directory separator */
  
  public function __construct($file)
  {
    /* template file with path relative to project root */
    $this->file = $file;
    $this->vars = array();
    $this->isCached = false; //initialize as NOT cached
    $this->prepareCacheManager();
    
    if (!$this->isCached()) {
      $this->parseTemplate();
    }
  }
  
  protected function parseTemplate()
  {
    //lookup included file placeholders and replace them with the corresponding file content
    $content = $this->fileLookup(file_get_contents($this->file));
    
    $this->recursiveParse($content, null, -1);
    $this->initializeVars($content);
    
    //reset the parent to the root node
    $this->parent = substr($this->parent, 0, strpos($this->parent, '.'));
  }
  
  /*
   * Identify the blocks and build the DOM tree
   * 
   */
  protected function recursiveParse($content, $parent, $level)
  {
    $level++;
    if (preg_match_all($this->genericNodePattern, $content, $matches)) {
      
      foreach ($matches[1] as $key => $blockName) {
        
        if (empty($this->parent) || $level == 0) {
          //root node
          $this->parent = $blockName;
        } else {
          //down the tree
          $nodes = explode('.', $this->parent);
          if ($level < count($nodes)) {
            while (count($nodes) > $level) {
              $lastNode = array_pop($nodes);
            }
            $this->parent = implode('.', $nodes);
          }
          $this->parent .= ".$blockName";
        }
        
        $this->tree[] = $this->parent;
        
        $this->recursiveParse($matches[2][$key], $parent, $level);
        $this->blocks[$blockName] = $matches[2][$key];
      }
    }
    $level--;
  }
  
  /*
   * Look for the presence of included files within the template
   * 
   */
  protected function fileLookup($content)
  {
    if (preg_match_all($this->fileLookupPattern, $content, $matches)) {
      foreach ($matches[1] as $key => $file) {
        $content = str_replace($matches[0][$key], file_get_contents($this->basePath. $file), $content);
      }
    }
    
    return $content;
  }
  
  protected function initializeVars($content)
  {
    if (preg_match_all($this->varsPattern, $content, $matches)) {
      foreach ($matches[1] as $key) {
        $this->vars[$key] = null;
      }
    }
  }
  
  protected function getLastNode($section)
  {
    $nodes = explode('.', $section);
    //this method parses only the last node content
    return array_pop($nodes);
  }
  
  protected function getChildren($section)
  {
    $matches = array();
    foreach ($this->tree as $branch) {
      $pattern = sprintf('/%s\.*([\.A-Za-z_0-9]+)$/', $section);
      
      if (preg_match($pattern, $branch, $match)) {
        $nodes = explode('.', $match[1]);
        $matches = array_merge($matches, $nodes);
      }
    }
    return array_reverse(array_unique($matches));
  }
  
  public function __set($name, $value)
  {
    if (is_object($value)) {
      $value = (array) $value;
    }
    
    if (is_array($value)) {
      foreach ($value as $k => $v) {
        $this->vars[sprintf('%s.%s', $name, $k)] = $v;
      }
    } else {
      $this->vars[$name] = $value;
    }
  }
  
  /*
   * Parsing is done in the reverse way
   * The deepest block gets parsed first followed by its immediate parent and then 
   * up the DOM tree
   * 
   */
  public function parse($section)
  {
    if ($this->isCached()) {
      //skip parsing of section - if it is a cached copy
      return;
    }
    
    $lastNode = $this->getLastNode($section);
    
    //fetch the section content (i.e. template section)
    $content = $this->blocks[$lastNode];
    
    //retrieve keys to be replaced
    $lookup = array_keys($this->vars);
    array_walk($lookup, function (&$val) {
      $val = sprintf('{%s}', $val);
    });
    
    //their replacement content
    $replace = array_values($this->vars);
    
    //determine any children of this section
    $children = $this->getChildren($section);
    
    //replace any child node content as well
    if (count($children) > 0) {
      foreach ($children as $child) {
        //check if the child block has already been parsed
        //NOTE: ensure that the child block is parsed before parsing its parent block
        if (isset($this->parsedBlocks[$child])) {
          $replacement = implode('', $this->parsedBlocks[$child]);
        } else {
          $replacement = null;
        }
        $pattern = str_replace('LOOKUP_NODE', $child, $this->lookupNodePattern);
        $content = preg_replace($pattern, $replacement, $content);
        //once the child content has been parsed - reset it
        //this prevents duplicates on subsequent parse within an iteration
        $this->parsedBlocks[$child] = array();
      }
    }
    
    //push the parsed content to the parsed block
    $this->parsedBlocks[$lastNode][] = str_replace($lookup, $replace, $content); 
  }
  
  public function text($section)
  {
    $lastNode = $this->getLastNode($section);
    return implode('', isset($this->parsedBlocks[$lastNode]) ? $this->parsedBlocks[$lastNode] : array());
  }
  
  public function render($section)
  {
    print $this->text($section);
  }

  public function resetParsedBlocks()
  {
    $this->parsedBlocks = array();
    //resetting parsed blocks invalidates cache
    $this->invalidateCache();
  }

  public function resetParsedBlock($node)
  {
    $this->parsedBlocks[$node] = array();
    //resetting a parsed block invalidates cache
    $this->invalidateCache();
  }
  
  /* cache related methods */
  
  protected function prepareCacheManager()
  {
    if (extension_loaded('memcached')) {
      $this->cacheManager = new \Cache\Memcached;
    } else {
      $this->cacheManager = null;
    }
  }
  
  /*
   * Tells if the template has been cached
   */
  public function isCached()
  {
    if ($this->isCached) {
      return true;
    }
    
    if (!$this->cacheManager instanceof \Cache\Manager) {
      return false;
    }
    
    $cachedValue = $this->cacheManager->get($this->getCacheKey());
    
    $this->unserialize($cachedValue);
    
    return $this->isCached;
  }
  
  /*
   * Used as a chain method before calling render
   * 
   */
  public function cache($expiresTime)
  {
    if ($this->cacheManager instanceof \Cache\Manager) {
      $this->cacheManager->set($this->getCacheKey(), $this->getSerializedValue(), $expiresTime);
    }
    
    return $this;
  }
  
  protected function getCacheKey()
  {
    return md5($this->file);
  }
  
  protected function invalidateCache()
  {
    if ($this->cacheManager instanceof \Cache\Manager) {
      $this->cacheManager->delete($this->getCacheKey());
    }
    $this->isCached = false;
  }
  
  protected function getSerializedValue()
  {
    $data = array(
      'parent'       => $this->parent,
      'tree'         => $this->tree,
      'vars'         => $this->vars,
      'blocks'       => $this->blocks,
      'parsedBlocks' => $this->parsedBlocks
    );
    return serialize($data);
  }
  
  protected function unserialize($data)
  {
    if (empty($data)) {
      return;
    }
    
    $data = unserialize($data);
    
    if (count($data) && !empty($data)) {    
      $this->parent = $data['parent'];
      $this->tree   = $data['tree'];
      $this->vars   = $data['vars'];
      $this->blocks = $data['blocks'];
      $this->parsedBlocks = $data['parsedBlocks'];
      
      $this->isCached = true;
    }
  }
}
