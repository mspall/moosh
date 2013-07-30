<?php
/**
 * moosh user-create [--password=<password> --email=<email>
 *                   --city=<city> --country=<CN>
 *                   --firstname=<firstname> --lastname=<lastname>]
 *                   <username1> [<username2> ...]
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle23\User;
use Moosh\MooshCommand;

class UserCreate extends MooshCommand
{
    public function __construct()
    {
        parent::__construct('create', 'user');
        $this->addOption('a|auth:', 'authentication plugin, e.g. ldap');
        $this->addOption('p|password:', 'password');
        $this->addOption('e|email:','email address');
        $this->addOption('c|city:','city');
        $this->addOption('C|country:','country');
        $this->addOption('f|firstname:','first name');
        $this->addOption('l|lastname:','last name');
        $this->addOption('i|idnumber:','idnumber');

        $this->addArgument('username');
        $this->maxArguments = 255;
    }

    public function execute()
    {
        global $CFG, $DB;

        require_once $CFG->dirroot . '/user/lib.php';
        unset($CFG->passwordpolicy);

        foreach ($this->arguments as $argument) {
            $this->expandOptionsManually(array($argument));
            $options = $this->expandedOptions;
            $user = new \stdClass();
            $user->auth = $options['auth'];
            if($options['password']){ // needed to stop errors when creating an LDAP user
                $user->password = $options['password'];
            }
            $user->email = $options['email'];
            $user->city = $options['city'];
            $user->country = $options['country'];
            $user->firstname = $options['firstname'];
            $user->lastname = $options['lastname'];
            $user->idnumber = $options['idnumber'];
            $user->timecreated = time();
            $user->timemodified = $user->timecreated;
            $user->username = $argument;

            $user->confirmed = 1;
            $user->mnethostid = $CFG->mnet_localhost_id;
            
            // to prevent errors about insufficiently strong passwords, use a
            // direct DB insert rather than an API call when adding a user
            // with external auth and no password specified
            if($options['auth'] && $options['auth'] != "manual" && !$options['password']){
                $newuserid = $DB->insert_record('user', $user);
            }else{
                $newuserid = user_create_user($user);
            }

            echo "$newuserid\n";
        }
    }
}
