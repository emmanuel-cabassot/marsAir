<?php 

class user {

    private $_db;
    
    public function __construct(){
        try {
            $db = new PDO('mysql:host=localhost;dbname=marsair', "root", "");
            $this->_db = $db;
        } catch (\Throwable $th) {
            echo $th->getMessage();
        }
    }


 // ////////////////////////////////////////

// ///////////////////////////////////////////////// FONCTION POUR S'INSCRIRE

// ////////////////////////////////////////


    public function inscription($firstname, $lastname, $email, $password, $confirmPassword)
        {
            $db = $this->_db;
            $msg = '';

            $firstname = htmlspecialchars($firstname);
            $lastname = htmlspecialchars($lastname);
            $email = htmlspecialchars($email);
            $password = htmlspecialchars($password);
            $confirmPassword = htmlspecialchars($confirmPassword);

            $_firstname = trim($firstname);
            $_lastname = trim($lastname);
            $_email = trim($email);
            $_password = trim($password);
            $_confirmPassword = trim($confirmPassword);
            $cryptage = password_hash($_password, PASSWORD_BCRYPT);

            $verification2 = $db->prepare("SELECT `email` FROM user WHERE email = '$_email'");
            $verification2->execute();

                if($_password == $_confirmPassword){
                    if($verification2->fetch(PDO::FETCH_ASSOC) == 0 ){
                                $requete = "INSERT INTO `user` ( `firstname`, `lastname`, `email`, `password`) VALUES ('$_firstname', '$_lastname', '$_email', '$cryptage')";
                                $db->query($requete);
                                $msg = 'Bienvenue !';                    
                    }else{
                        $msg = 'Cette email est déja utilisé';
                    }
                }else{
                    $msg = 'Les mots de passes ne sont pas identiques';
                }

            return(json_encode($msg)); 

        }

}

?>