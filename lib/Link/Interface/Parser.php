<?php
/**
 * This file is part of Link TPL.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 *
 * @copyright Copyleft (c) 2007+, Baptiste Clavié, Talus' Works
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.opensource.org/licenses/BSD-3-Clause Modified BSD License
 * @version $Id: 22d725ff4fe9a43e608b970f3f86d9b70a61d781 $
 */

/**
 * Interface to implement a new Parser for the templates.
 *
 * @package Link
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 */
interface Link_Interface_Parser {
  /**
   * Accessor for a given parameter
   *
   * @param string $name Parameter's name
   * @param mixed $val Parameter's value (if setter)
   * @return mixed Parameter's value
   */
  public function parameter($name, $val = null);

  /**
   * Transform a TPL syntax towards an optimized PHP syntax
   *
   * @param string $str TPL script to parse
   * @return string
   */
  public function parse($str);
}

/*
 * EOF
 */
