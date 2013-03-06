<?php

class recover_pass extends rcube_plugin
{
	public $task = 'settings';
	private $rc;
	private $obj;
	private $startdate;
	private $enddate;
	private $dbh;
        private $db_user;
        private $db_pass;
        private $db_name;
        private $db_type;
        private $db_host;


/*
 * Initializes the plugin.
 */
	public function init()
	{
		$rcmail = rcmail::get_instance();

		$this->rc = &$rcmail;

		// Load the localization files
		$this->add_texts('localization/', true);
			

		$config_db = rcmail::get_instance()->config->get('db_dsnw');

                $this->create_db_pdo($config_db);


		// Action to take when the vacation button is pressed
		$this->register_action('plugin.recover_pass', array($this, 'recover_pass_init'));

		// Action to take when the save button is pressed
		$this->register_action('plugin.recover_pass-save', array($this, 'recover_pass_save'));

		// Action to take when the delete button is pressed
		$this->register_action('plugin.recover_pass-delete',array($this,'recover_pass_delete'));

		// Action to take when the cancel button is pressed
		//$this->register_action('plugin.vacation-cancel',array($this,'vacation_cancel'));

		// Include the js file for the UI and listening for events
		$this->include_script('recover_pass.js');

	}

	function recover_pass_init(){
		$this->register_handler('plugin.body', array($this, 'recover_pass_form'));
		$this->rc->output->set_pagetitle($this->gettext('recover_pass'));
		$this->rc->output->send('plugin');
	}



	function recover_pass_save(){
		
	
		$username = rcmail::get_instance()->user->get_username();

		$do=get_input_value('do',RCUBE_INPUT_POST);
	
		$recover_pass_question = get_input_value('_recover_pass_question', RCUBE_INPUT_POST);	
		$recover_pass_answer_1 = get_input_value('_recover_pass_answer_1', RCUBE_INPUT_POST);
		$recover_pass_answer_2 = get_input_value('_recover_pass_answer_2', RCUBE_INPUT_POST);
//		$recover_email = get_input_value('_recover_email', RCUBE_INPUT_POST);
		$recover_pass_current_pass_1 = get_input_value('_recover_pass_current_pass_1', RCUBE_INPUT_POST);
		$recover_pass_current_pass_2 = get_input_value('_recover_pass_current_pass_2', RCUBE_INPUT_POST);

		
		if($do=='save'){


				if($recover_pass_question != '' && $recover_pass_answer_1 != '' && $recover_pass_answer_2 != '' && $recover_pass_answer_1 == $recover_pass_answer_2  && $recover_pass_current_pass_1 == $recover_pass_current_pass_2 && $recover_pass_current_pass_1 != '' && $recover_pass_current_pass_2 != ''){


					echo $recover_pass_question.'<br>';
					echo $recover_pass_answer_1.'<br>';
					echo $recover_pass_answer_2.'<br>';
//					echo $recover_email.'<br>';
					echo $username.'<br>';
					echo $recover_pass_current_pass_1.'<br>';
					echo $recover_pass_current_pass_2.'<br>';


					$sth = $this->dbh->prepare('REPLACE INTO recover_pass (user,question,answer,pass) VALUES (?,?,?,?)');

			                $res = $sth->execute(array($username,$recover_pass_question,$recover_pass_answer_1,$recover_pass_current_pass_1));

                			if(!$res){

                        			echo 'SQL error in inserting data';
						
						
                                                $msg = $this->gettext('save_fail');

                                                $this->rc->output->show_message($msg,'error');

                			}else{
					
						$msg = $this->gettext('save');

	                                        $this->rc->output->show_message($msg);


						$this->register_handler('plugin.body', array($this, 'recover_pass_form'));
                                        	$this->rc->output->set_pagetitle($this->gettext('recover_pass'));
                                        	$this->rc->output->send('plugin');

					}

				}else{
					
					if($recover_pass_question == '')
						$msg = $this->gettext('no_secret_question');
					
					if($recover_pass_answer_1 == '')
						$msg .= $this->gettext('no_secret_answer');

					if($recover_pass_answer_2 == '')
						$msg .= $this->gettext('no_secret_answer_confirm');

//					if($recover_email == '')
//						$msg .= $this->gettext('no_recover_email');

					if($recover_pass_current_pass_1 == '')
						$msg .= $this->gettext('no_current_pass');

					if($recover_pass_current_pass_2 == '')
						$msg .= $this->gettext('no_current_pass_confirm');			


					if($recover_pass_answer_1 != $recover_pass_answer_2 && $recover_pass_question != '' && $recover_pass_answer_1 != '' && $recover_pass_2 != '' )
						$msg .= $this->gettext('no_match_question');
	
				
					if($recover_pass_current_pass_1 != '' && $recover_pass_current_pass_2 != '' && $recover_pass_current_pass_1 != $recover_pass_current_pass_2)
					$msg .= $this->gettext('no_match_pass');

					$this->rc->output->show_message($msg,'error');

                                	$this->register_handler('plugin.body', array($this, 'recover_pass_form'));
                                	$this->rc->output->set_pagetitle($this->gettext('recover_pass'));
                                	$this->rc->output->send('plugin');
		
				}


		}elseif($do=='delete'){


			 $sth = $this->dbh->prepare('DELETE FROM recover_pass WHERE user = ?');

                         $res = $sth->execute(array($username));

                         if(!$res){

                         	echo 'SQL error in deleting data ';

                         }



			$this->rc->output->show_message($this->gettext('delete'));

                        $this->register_handler('plugin.body', array($this, 'recover_pass_form'));
                        $this->rc->output->set_pagetitle($this->gettext('recover_pass_title'));
                        $this->rc->output->send('plugin');


		}
}




public function recover_pass_form()
{


	$username = rcmail::get_instance()->user->get_username();

	$sth = $this->dbh->prepare('SELECT *  FROM recover_pass WHERE user = ?');

        $res = $sth->execute(array($username));

	
    	if(!$res){

   		echo 'SQL error in deleting data after login';

     	}else{
	
		$sth->setFetchMode(PDO::FETCH_ASSOC);

                $row = $sth->fetch();

                $recover_pass_question = $row['question'];

		$recover_pass_answer_1 = $row['answer'];

		$recover_pass_answer_2 = $row['answer'];

//		$recover_email = $row['recover_email'];

		$recover_pass_current_pass_1 = $row['pass'];

		$recover_pass_current_pass_2 = $row['pass'];

	}

	

	
	$table = new html_table();


	$table->add_row();

	$field_id = 'recover_pass_question';

	$table->add('title', html::label($field_id, Q($this->gettext('recover_pass_question'))));

	$input_recover_pass_question = new html_textarea(array('name' => '_recover_pass_question', 'id' => $field_id, 'spellcheck' => 1, 'rows' => 1, 'cols' => 40, 'class' => 'mce_editor'));

	$table->add(null, $input_recover_pass_question->show($recover_pass_question));




        $table->add_row();

        $field_id = 'recover_pass_answer_1';

        $table->add('title', html::label($field_id, Q($this->gettext('recover_pass_answer_1'))));

        $input_recover_pass_answer_1 = new html_passwordfield(array('name' => '_recover_pass_answer_1', 'id' => $field_id, 'spellcheck' => 1, 'size' => 53, 'class' => 'mce_editor', 'autocomplete' => 'off'));

        $table->add(null, $input_recover_pass_answer_1->show($recover_pass_answer_1));





        $table->add_row();

        $field_id = 'recover_pass_answer_2';

        $table->add('title', html::label($field_id, Q($this->gettext('recover_pass_answer_2'))));

        $input_recover_pass_answer_2 = new html_passwordfield(array('name' => '_recover_pass_answer_2', 'id' => $field_id, 'spellcheck' => 1, 'size' => 53, 'class' => 'mce_editor', 'autocomplete' => 'off'));

        $table->add(null, $input_recover_pass_answer_2->show($recover_pass_answer_2));




        $table->add_row();

        $field_id = 'recover_pass_current_pass_1';

        $table->add('title', html::label($field_id, Q($this->gettext('recover_pass_current_pass_1'))));

        $input_recover_pass_current_pass_1 = new html_passwordfield(array('name' => '_recover_pass_current_pass_1', 'id' => $field_id, 'spellcheck' => 1, 'size' => 53, 'class' => 'mce_editor', 'autocomplete' => 'off'));

        $table->add(null, $input_recover_pass_current_pass_1->show($recover_pass_current_pass_1));





        $table->add_row();

        $field_id = 'recover_pass_current_pass_2';

        $table->add('title', html::label($field_id, Q($this->gettext('recover_pass_current_pass_2'))));

        $input_recover_pass_current_pass_2 = new html_passwordfield(array('name' => '_recover_pass_current_pass_2', 'id' => $field_id, 'spellcheck' => 1, 'size' => 53, 'class' => 'mce_editor', 'autocomplete' => 'off'));

        $table->add(null, $input_recover_pass_current_pass_2->show($recover_pass_current_pass_2));




//	$table->add_row();

//	$field_id = 'recover_email';

//	$table->add('title', html::label($field_id, Q($this->gettext('recover_email'))));

//        $input_recover_email = new html_inputfield(array('name' => '_recover_email', 'id' => $field_id, 'spellcheck' => 1, 'size' => 53, 'class' => 'mce_editor', 'autocomplete' => 'off'));

//        $table->add(null, $input_recover_email->show($recover_email));




	if ($recover_pass_question != '' )
	{
		$mylabel = 'recover_pass_set';	
	}
	else{
		$mylabel = 'no_recover_pass_set';
	}
	
	$table->add_row();
	
	$table2 = new html_table();

	$table2->add(null,html::p(null, $this->rc->output->button(array('command' => 'plugin.recover_pass-save','class'=>'button mainaction', 'type' => 'input',	'label' => $this->gettext('save_button')))));

	$table2->add(null,html::p(null, $this->rc->output->button(array('command' => 'plugin.recover_pass-delete','class'=>'button mainaction', 'type' => 'input',  'label' => $this->gettext('delete_button')))));

	

	$out =  html::div(array('class'=>'box'),html::div(array('id'=>'prefs-title','class'=>'boxtitle'),$this->gettext($mylabel)).html::div(array('class'=>'boxcontent'), $table->show())) ;
	
	$out .= $table2->show();
		
	$this->rc->output->add_gui_object('recover_pass_form', 'recover_pass_form');

	//return $table;
	return $this->rc->output->form_tag(array('id' => 'recover_pass_form', 'name' => 'recover_pass_form', 'method' => 'post', 'action' => './?_task=settings&_action=plugin.recover_pass-save'), $out);

}

	function create_db_pdo($config_db){


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

                $dsn = $this->db_type.':dbname='.$this->db_name.';host='.$this->db_host;

                try {

                        $this->dbh = new PDO($dsn, $this->db_user, $this->db_pass);

                } catch (PDOException $e) {

                        echo 'Connection failed: ' . $e->getMessage();

                }

        }


}
?>
