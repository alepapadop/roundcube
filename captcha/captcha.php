<?php
session_start();

require_once('encryption.php');

class captcha extends rcube_plugin
{
	
	// default values
	private $text_type = 'math';
	private $char_num = 5;
	private $num_num = 5;
	private $allowed_fails = 3;
	private $ban_timestamp;
	private $now_timestamp;
	private $dbh;
	private $db_user;
	private $db_pass;
	private $db_name;
	private $db_type;
	private $db_host;
	private $client_ip;
	private $captcha;
	private $loginform;	
	private $key = 'roundcube_sucks';

	function init()
	{

		$this->add_texts('localization/', true);

		$this->ban_timestamp = strtotime('-5 minutes');		
		$this->now_timestamp = strtotime('now');

		$this->add_hook('template_object_loginform', array($this, 'loginform'));
                $this->add_hook('authenticate', array($this, 'auth'));
                $this->add_hook('login_failed', array($this, 'login_fail'));
		$this->add_hook('login_after', array($this, 'login_pass'));
                $this->add_hook('session_destroy', array($this, 'session'));

		
		$config_type = rcmail::get_instance()->config->get('captcha_type');
                $config_char_num = rcmail::get_instance()->config->get('captcha_char_num');
		$config_num_num = rcmail::get_instance()->config->get('captcha_num_num');
                $config_allowed_fails = rcmail::get_instance()->config->get('captcha_allowed_fails');
		$config_ban_time = rcmail::get_instance()->config->get('captcha_ban_time'); 
		$config_db = rcmail::get_instance()->config->get('db_dsnw');		

		$this->create_db_pdo($config_db);

		
		if(is_int($config_ban_time) && $config_ban_time>0 )
			$this->ban_timestamp = strtotime('-'.$config_ban_time.' minutes');

		if($config_type == 'text')
                        $this->text_type = 'text';

                if($config_char_num < 10 && $config_char_num > 3 &&  is_numeric($config_char_num) )
                        $this->char_num = $config_char_num;

		if($config_num_num < 10 && $config_num_num > 3 &&  is_numeric($config_num_num) )
                        $this->num_num = $config_num_num;


                if(isset($config_allowed_fails) > 0 && is_int($config_allowed_fails ))
                        $this->allowed_fails = $config_allowed_fails;

		
	}


	function session(){

		

		//echo 'pass: '.$_POST['answer'];

	}

	function login_pass(){

		$this->client_ip = $_SERVER['REMOTE_ADDR'];
	
		$sth = $this->dbh->prepare('DELETE FROM captcha WHERE ip = ?');

		$res = $sth->execute(array($this->client_ip));
		
		if(!$res){
			
			echo 'SQL error in deleting data after login';
		
		}
	}

	function login_fail(){
	

		$this->client_ip = $_SERVER['REMOTE_ADDR'];
		
		$sth = $this->dbh->prepare('SELECT hits FROM captcha hits WHERE ip = ?');

		$res = $sth->execute(array($this->client_ip));

		if($res){

                        $sth->setFetchMode(PDO::FETCH_ASSOC);

                   	$row = $sth->fetch();
			
			$hits = $row['hits'] + 1;			

		}else{

			$hits = 0;

		}

		$sth = $this->dbh->prepare('REPLACE INTO captcha (ip,hits,timestamp)  VALUES (?,?,?) ');

                $res = $sth->execute(array($this->client_ip,$hits,$this->now_timestamp));

                if(!$res){

                        echo 'SQL error in updating data after login fail';
                }
	}

	function create_db_pdo($config_db){
	
		//echo  $config_db;
		
		$a = explode('@',$config_db);
		
		$host_database = $a[1];

		$host_database_parts = explode('/',$a[1]);

		$this->db_host = $host_database_parts[0];

		$this->db_name = $host_database_parts[1];

		$type_credentials_parts = explode('://',$a[0]);

		$this->db_type = $type_credentials_parts[0];

		$credentials_parts = explode(':',$type_credentials_parts[1]);

		$this->db_user = $credentials_parts[0];

		$this->db_pass = $credentials_parts[1];
		
		//echo '<br>';
		//echo $this->db_user;	
		//echo '<br>';
		//echo $this->db_password;	
		//echo '<br>';
		//echo $this->db_host;
		//echo '<br>';
		//echo $this->db_name; 
                //echo '<br>';  	
		//echo $this->db_type;
	
	
		$dsn = $this->db_type.':dbname='.$this->db_name.';host='.$this->db_host;

		
		try {

    			$this->dbh = new PDO($dsn, $this->db_user, $this->db_pass);

		} catch (PDOException $e) {

    			echo 'Connection failed: ' . $e->getMessage();

		}		


	
	}


	
	function send_email(){

		$sth = $this->dbh->prepare('SELECT answer, recover_email  FROM recover_pass WHERE user = ?  LIMIT 1');

            	$res = $sth->execute(array($username));

           	if($res){

             		$sth->setFetchMode(PDO::FETCH_ASSOC);

                 	$row = $sth->fetch();
			
			if($row != ''){

				if( $row['answer'] == $_POST['answer']){
				
					
			                //$sth = $this->dbh->prepare('SELECT answer, recover_email  FROM recover_pass WHERE user = ?  LIMIT 1');

                			//$res = $sth->execute(array($username));


					//mail($recover_email, 'Password Recovery',,'From: Reoundcube Server');

				}

			}

		}
	

	}	


	function loginform($loginform){
		



		if(isset($_POST)){
			
			//echo $_POST['answer'].'<br>';
			//echo $_POST['user'].'<br>';
			
			$username = $_POST['user'];

			$answer = $_POST['answer'];

			$sth = $this->dbh->prepare('SELECT pass, answer  FROM recover_pass WHERE user = ?  LIMIT 1');

                        $res = $sth->execute(array($username));
		
			if($res){

                 	       	$sth->setFetchMode(PDO::FETCH_ASSOC);

            		       	$row = $sth->fetch();

                               	if($row!=''){

					if($row['answer'] == $answer && $_POST['captcha'] == $_POST['a']){
						
						
						echo '<script>';
						echo 'alert("'.$this->gettext('pass_is').' : '.$row['pass'].'")';
						echo '</script>';		
					
					}elseif($row['answer'] == $answer && $_POST['captcha'] != $_POST['a']){

						echo '<script>';
                                                //echo 'alert(" The captcha is wrong ")';
						echo 'alert("'.$this->gettext('captcha_wrong').'")';
                                                echo '</script>';

					}elseif($row['answer'] != $answer && $_POST['captcha'] == $_POST['a']){

						echo '<script>';
                                                //echo 'alert(" The answer is wrong ")';
						echo 'alert("'.$this->gettext('answer_wrong').'")';
                                                echo '</script>';


					}elseif($row['answer'] != $answer && $_POST['captcha'] != $_POST['a']){
				
						echo '<script>';
                                                //echo 'alert(" The captcha and the answer is wrong ")';
						echo 'alert("'.$this->gettext('captcha_answer_wrong').'")';
                                                echo '</script>';


					}

				}
			}

		}
    		$this->client_ip = $_SERVER['REMOTE_ADDR'];

		//$dbh = new PDO('mysql:dbname=roundcube;host=127.0.0.1','username','password');
		
		if($_GET['recover']=="true"){

			$tmp = $loginform['content'];

			$username = $_GET['user'];

			//echo $username;

			$sth = $this->dbh->prepare('SELECT question  FROM recover_pass WHERE user = ?  LIMIT 1');

                     	$res = $sth->execute(array($username));

			
			
			if($username == ''){
				
				$tmp = '<table>
                                        <tr>
                                        <td>
                                        	<center><font color = "white">'.$this->gettext('no_username').'</font></center><br>
                                        </td>
                                        </tr>
                                        <tr>
                                       	<td align = "center">
                                        	<input type = "submit" name = "no_question" value = "'.$this->gettext('back').'" class = "button mainaction">
                                       	</td>
                                        </tr>
                                        </table>
                                        </form></div><div style="visibility:hidden"><div style="visibility:hidden"><form>';

					$loginform['content'] = $tmp;

			}else{

				if($res){

					$sth->setFetchMode(PDO::FETCH_ASSOC);

	                                $row = $sth->fetch();

					if($row!=''){
				
						
						$this->captcha = $this->captcha_text($this->text_type,$this->char_num,$this->num_num);

	                                        //print_r($this->captcha);


						$tmp = '<table>
							<tr>
							<td>
								<font color="white">'.$this->gettext('question').'</font>
							</td>
							<td>
							<font color = "white">	'.$row['question'].'</font>
							</td>
							</tr>
							<tr>
							<td>
								<font color= "white">'.$this->gettext('answer').'</font>
							</td>
							<td>
								<input type = "text" name="answer">
							</td>
							</tr>
							<tr>
                                                        <td>&nbsp</td>
                                                        </tr>
                                                        <tr>
                                                        <td colspan = "2" align = "center" ><font color="white">
                                                        '.$this->captcha['user_text'].':   '.$this->captcha['msg'].'
                                                        </font></td>
                                                        </tr>
                                                        <tr>
                                                        <td colspan ="2">
                                                                <input type="text" name="captcha" id="captcha_id">
                                                                <input type="hidden" name="a"  value="'.$this->captcha['value'].'" >
                                                        </td>
                                                        </tr>
							<tr>
                                                        <td>&nbsp</td>
                                                        </tr>
                                                        <tr>
							<tr>
							<td colspan = "2" align = "center" >
								<input type = "submit" name="answer_button" value = "'.$this->gettext('submit').'" class = "button mainaction">
								<input type = "hidden" name="user" value = "'.$username.'">
							</td>
							</tr>
							</table>
							</form></div><div style="visibility:hidden"><div style="visibility:hidden"><form>';

					}else{
					
					
						$tmp = '<table>
							<tr>
							<td>
								<center><font color = "white">'.$this->gettext('no_secret').'</font></center>
							</td>
							</tr>
							<tr>
							<td align = "center">
								<input type = "submit" name = "no_question" value = "'.$this->gettext('back').'" class = "button mainaction">
							</td>
							</tr>
							</table>
								</form></div><div style="visibility:hidden"><div style="visibility:hidden"><form>';


					}


				}else{
				
					echo 'SQL error in selecting secret question';

				}
		

				$loginform['content'] = $tmp;

			}

		}else{

			$sth = $this->dbh->prepare('SELECT timestamp  FROM captcha WHERE ip = ? AND hits >= ? LIMIT 1');
		
			$res = $sth->execute(array($this->client_ip,$this->allowed_fails));
	
			if($res){
	
				$sth->setFetchMode(PDO::FETCH_ASSOC); 
		
				$row = $sth->fetch();
	
				if($this->ban_timestamp > $row['timestamp'] && $row != ''  ){
	
					
					$sth = $this->dbh->prepare('DELETE FROM captcha WHERE ip = ?');
			
					$res = $sth->execute(array($client_ip));

					if(!$res){

						echo 'SQL error in deleting client data<br>';
						print_r($sth->errorInfo());

					}	


                        	}elseif($row != ''){
			

					//SQL select for question
					// add question in the table
					//open a pop up for the secret question
					// if they match send an email with the password of the user


					$this->captcha = $this->captcha_text($this->text_type,$this->char_num,$this->num_num);

        	        		print_r($this->captcha);

	                		$tmp = $loginform['content'];
                
					$tmp = str_ireplace('</tbody>',
                                   			'<tr>
							<td>&nbsp</td>
							</tr>
							<tr>
                                        		<td colspan = "2" align = "center" ><font color="white">
                                                	'.$this->captcha['user_text'].':   '.$this->captcha['msg'].'
                                        		</font></td>
							</tr>
							<tr>
                                        		<td colspan ="2">
                                                		<input type="text" name="captcha" id="captcha_id">
                                                		<input type="hidden" name="a"  value="'.$this->captcha['value'].'" >
                                        		</td>
                                     			</tr>
                                     			</tbody>', $tmp);

                			$loginform['content'] = $tmp;				


				} 


			}else{
			
				echo 'SQL error in retrieving client data<br>';
			
			}

		

		 	$tmp = $loginform['content'];



			//echo dirname(__FILE__);
			//echo $_SERVER['SERVER_ADDR'];
			//echo $_SERVER['SERVER_NAME'];
			//$path = $_SERVER['SERVER_NAME'].'/plugins/captcha/send_email.php';

			$tmp = $tmp.'<center><a href="#" onClick = "ok()">'.$this->gettext('recover_pass').'</a></center>
					<script>
						function ok()
						{
							var x = document.getElementById("rcmloginuser").value;
							//alert(x);
							
							window.location.href = "?recover=true&user="+x;

						}
					</script>';

		//$tmp = $tmp.' <input type = "submit" value = "Recover Password" name = "recover_pass" class="button mainaction" onclick="myPopup()"  />
		//		</script></form></div><div style="visibility:hidden"><div style="visibility:hidden"><form>';


          		$loginform['content'] = $tmp;

		}

		return $loginform;	

	}

	function auth($auth){
	
		//echo 'pass: '.$_POST['answer_button'];	
	
		$rcmail = rcmail::get_instance();

		if($_POST['a'] != $_POST['captcha']){
			
			$rcmail->output->show_message('rcguard.recaptchafailed', 'error');
			$rcmail->output->set_env('task', 'login');
        		$rcmail->output->send('login');
			die();
		}
		
		return $auth;
	}



	private function captcha_text($text_type ,$char_num, $num_num)
	{
			
		if($text_type == 'math'){
		
			$first_rand_int = rand(0,100);
			$second_rand_int = rand(0,100);
			$third_rand_int = rand(0,100);
			
			$first_operator = rand(0,1);
			$second_operator = rand(0,1);
			
			//echo $first_rand_int.'<br>';
			//echo $first_operator.'<br>';
			//echo $second_rand_int.'<br>';
			//echo $second_operator.'<br>';
			//echo $third_rand_int.'<br>';
			
			//echo '<br>';	

			if($first_operator == 0){
				$value = $first_rand_int + $second_rand_int;
				$msg = $first_rand_int.' + '.$second_rand_int;
				//echo $value.'<br>';

			}else{
				$value = $first_rand_int -  $second_rand_int;
				$msg = $first_rand_int.' - '.$second_rand_int;
				//echo $value.'<br>';
			}
			if($second_operator == 0 ){
				$value = $value +  $third_rand_int;
				$msg = $msg.' + '.$third_rand_int;
				//echo $value.'<br>';
			}else{
				$value = $value -  $third_rand_int;
				$msg = $msg.' - '.$third_rand_int;
				//echo $value.'<br>';
			}

			$user_text = $this->gettext('math');

		}elseif($text_type == 'text'){

			$char_set = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		
			$num_set = '1234567890';

			$char_set_size = strlen($char_set);
			
			$num_set_size = strlen($num_set);
						
			$total_size = $char_num + $num_num;
			
			// value type 0 is for character value
			// value type 1 is for number value
			$value_type = rand(0,1);			
			
			if($value_type == 0)
				$user_text = $this->gettext('letters');
			else
				$user_text = $this->gettext('numbers');

			for($i=0; $i<$total_size; $i++){
				
				// random 0 is for msg character value
				// randow 1 is for msg number value
				$random = rand(0,1);	
	
				if($random == 0 && $char_num>0){			
					
					$char_num = $char_num - 1;	
					
					$char = $char_set[ rand(0,$char_set_size) ];

					if($value_type == 0)	
						$value .= $char;
 
					$msg .= $char;
			
				}elseif($random == 1 && $num_num>0){

					$num_num = $num_num - 1;

					$num = $num_set[ rand(0,$num_set_size) ];
					
					if($value_type == 1)
                                        	$value .= $num;
					
					$msg .= $num;


				}

			}
	
		}
		
		return array('value'=>$value,'msg'=>$msg,'user_text'=>$user_text);

	}


}




?>

