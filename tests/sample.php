<?php

/**
 * This file is part of PHP Mess Detector.
 *
 * Copyright (c) Manuel Pichler <mapi@phpmd.org>.
 * All rights reserved.
 *
 * Licensed under BSD License
 * For full copyright and license information, please see the LICENSE file.
 * Redistributions of files must retain the above copyright notice.
 *
 * @author Manuel Pichler <mapi@phpmd.org>
 * @copyright Manuel Pichler. All rights reserved.
 * @license https://opensource.org/licenses/bsd-license.php BSD License
 * @link http://phpmd.org/
 */

//some comment
$a = 1 + 2; //inline comment

/* multiline comment
    line 1
    line 2
    line 3
*/

/** dockblock comment */

# hash comment

/**
 * class dockblock
 * @SuppressWarnings(PHPMD)
 * @see https://www.example.com
 */
class myClass
{
    /**
     * function dockblock
     */
    public function __construct()
    {
        //in function code
        /** in function dockblock */

        // $abc = $a + $b + $c;
        //todo: in function todo
        //fixme: in function fixme
    }
}
