<?php
/**
* @package SPLIB
* @version $Id: DB_Pager_Sliding.php,v 1.1 2003/08/16 16:38:05 harry Exp $
*/
/**
 * DB_Pager_Sliding - extends PEAR::Pager_Sliding
 * Provides an API to help build query result pagers
 * @access public
 * @package SPLIB
 */
class DB_Pager_Sliding extends Pager_Sliding {
    /**
    * DB_Pager_Sliding constructor
    * @param array params for parent
    * @access public
    */
    function DB_Pager_Sliding ($params) {
        parent::Pager_Sliding($params);
    }

    /**
    * Returns the number of rows per page
    * @access public
    * @return int
    */
    function getRowsPerPage () {
        return $this->_perPage;
    }

    /**
    * The row number to start a SELECT from
    * @access public
    * @return int 
    */
    function getStartRow () {
        if ( $this->_currentPage == 0 )
            return $this->_perPage;
        else
            return ( ($this->_currentPage - 1) * $this->_perPage );
    }
	
	    /**
     * Returns a string with a XHTML SELECT menu,
     * useful for letting the user choose how many items per page should be
     * displayed. If parameter useSessions is TRUE, this value is stored in
     * a session var. The string isn't echoed right now so you can use it
     * with template engines.
     *
     * @param integer $start
     * @param integer $end
     * @param integer $step
     * @return string xhtml select box
     * @access public
     */
    function getPerPageSelect($start=10, $end=50, $step=10)
    {
        $start = (int)$start;
        $end   = (int)$end;
        $step  = (int)$step;
        if (!empty($_SESSION[$this->_sessionVar])) {
            $selected = (int)$_SESSION[$this->_sessionVar];
        } else {
            $selected = $start;
        }

        $tmp = '<select name="'.$this->_sessionVar.'">';
        for ($i=$start; $i<=$end; $i+=$step) {
            $tmp .= '<option value="'.$i.'"';
            if ($i == $selected) {
                $tmp .= ' selected="selected"';
            }
            $tmp .= '>'.$i.'</option>';
        }
        $tmp .= '</select>';
        return $tmp;
    }
}
?>