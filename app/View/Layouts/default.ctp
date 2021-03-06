<?php
/**
 *
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.View.Layouts
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

$cakeDescription = __d('cake_dev', 'CakePHP: the rapid development php framework');
?>
<!DOCTYPE html>
<html>
<head>
	<?php echo $this->Html->charset(); ?>
	<title>
	</title>

    <script type="text/javascript">var myBaseUrl = '<?php echo $this->Html->url('/'); ?>';</script>


	<?php
		echo $this->Html->meta('icon');

		echo $this->Html->css('cake.generic');

		echo $this->fetch('meta');
		echo $this->fetch('css');
		echo $this->fetch('script');

        echo $this->Js->writeBuffer();
	?>
</head>
<body>
	<div id="container">
		<div id="header">

            <nav>
                <ul>

                    <?php
                    if($userData != null){
                        echo "<li><a href=\"". $this->webroot. "\">Home</a></li>";
                        echo "<li><a href=\"". $this->webroot. "terms\">Termini</a></li>";
                        if($userData['role'] == 'Menadžer')
                        {
                            echo "<li><a href=\"". $this->webroot. "users\">Korisnici</a></li>";
                            echo "<li><a href=\"". $this->webroot. "settings/edit/\">Podešavanja</a></li>";
                        }

                        echo "<li><a href=\"". $this->webroot. "terms/izvjestaj\">Izvještaj</a></li>";
                        echo "<li><a href=\"". $this->webroot. "users/edit/". $userData['id']."\">Uredi profil</a></li>";
                        echo "<li id=\"logout\"><a href=\"". $this->webroot. "users/logout\" >Logout(".$userData['name'].")</a></li>";
                    }
                    else {

                    }
                    ?>

                </ul>
            </nav>

		</div>
		<div id="content">
                <?php echo $this->Session->flash(); ?>
                <?php echo $this->fetch('content'); ?>
		</div>
		<div id="footer">

		</div>
	</div>
</body>
</html>
