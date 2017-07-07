<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="styles/main_style.css" type="text/css">
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <!-- jQuery library -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.0/jquery.min.js"></script>
    <!-- Latest compiled JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="styles/custom_nav.css" type="text/css">
    <title>Final Project</title>
</head>

<body>
<?php
require_once('create_puzzle.php');
require_once('common_sql_functions.php');
require_once('utility_functions.php');
session_start();
require_once('session_validation.php');
?>
<?PHP echo getTopNav(); ?>
<!--FIXME: location of fail message not displayed properly-->
<div id="pop_up_fail" class="pop_up" style="display:none">
    <div class="pop_up_background">

        <img class="pop_up_img_fail" alt="fail puzzle pop up" src="pic/info_circle.png">
        <div class="pop_up_text">Incorrect! <br>Try Again!</div>
        <button class="pop_up_button" onclick="change_display_none('pop_up_fail')">OK</button>
    </div>
</div>
<!--TODO: Needs to give option to play again if they guess correctly-->
<?php
$words = "";
$nameEntered = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['randomPlay']) && isset($_POST['puzzleWord'])) { //random puzzle
        $puzzleName = validate_input($_POST['puzzleWord']);
        if (strlen($puzzleName) > 0) {
            $puzzle = new Puzzle($puzzleName, -1, -1, 2, 20);
            $words = $puzzle->js_solution;
            echo $puzzle->htmlTable;
            echo $puzzle->buttons;
            $puzzle->createPuzzleInDB();
        } else {
            tryAgain();
        }
    } else if (isset($_POST['iDesign']) && isset($_POST['puzzleWord'])) { // I will design
        //saving on refresh
        $puzzleName = validate_input($_POST['puzzleWord']);
        if (strlen($puzzleName) > 0) {
            if (isset($_POST['maxLength']) && isset($_POST['minLength']) && isset($_POST['position'])) {
                echo "<form method='POST' action='#'>";
                $preferedPosition = (int)validate_input($_POST['position']);
                $minLength = (int)validate_input($_POST['minLength']);
                $maxLength = (int)validate_input($_POST['maxLength']);
                $puzzle = new Puzzle($puzzleName, -1, $preferedPosition, $minLength, $maxLength);
                $words = $puzzle->js_solution;
                echo $puzzle->createAdminInputBoxes();
                echo $puzzle->admin_buttons;
                echo "</form>";
            } else {
                echo "<form method='POST' action='#'>";
                $puzzle = new Puzzle($puzzleName, -1, 2, 2, 20);
                $words = $puzzle->js_solution;
                echo $puzzle->createAdminInputBoxes();
                echo $puzzle->admin_buttons;
                echo "</form>";
            }
        } else {
            tryAgain();
        }
    } else if (isset($_POST['saveIDesign'])) { // save button pressed
        $puzzleInfo = pullInputFromSave();
        $clue_id_array = array_pop($puzzleInfo);
        $word_id_array = array_pop($puzzleInfo);
        $puzzleName = array_pop($puzzleInfo);
      //  var_dump($word_id_array);
        savePuzzle($puzzleName, $word_id_array, $clue_id_array);
        echo "<script>alert('Puzzle: $puzzleName was saved.');</script>";
        echo "<form method='POST' action='#'>";
        $puzzleName = validate_input($_POST['puzzleWord']);
        $preferedPosition = (int)validate_input($_POST['position']);
        $minLength = (int)validate_input($_POST['minLength']);
        $maxLength = (int)validate_input($_POST['maxLength']);
        $puzzle = new Puzzle($puzzleName, -1, $preferedPosition, $minLength, $maxLength);
        $words = $puzzle->js_solution;
        echo $puzzle->createAdminInputBoxes();
        echo $puzzle->admin_buttons;
        echo "</form>";
    } else if(isset($_POST['UpdateImages'])) {
        $puzzleName = $_POST['puzzleWord'];
        $puzzleInfo = updatePuzzle();
    }
} else if (isset($_GET['puzzleName']) && isset($_GET['id'])) { // play button from puzzle list
    $puzzleName = validate_input($_GET['puzzleName']);
    if (strlen($puzzleName) > 0) {
        $puzzle_id = (int)validate_input($_GET['id']);
        $puzzle = new Puzzle($puzzleName, $puzzle_id, -1, 2, 20);
        $words = $puzzle->js_solution;
        echo $puzzle->htmlTable;
        echo $puzzle->buttons;
    } else {
        tryAgain();
    }
}
else if (isset($_GET['puzzleName'])) {  // come back to play puzzle from login
    $puzzleName = validate_input($_GET['puzzleName']);
    $puzzle = new Puzzle($puzzleName, -1, -1, 2, 20);
    $words = $puzzle->js_solution;
    echo $puzzle->htmlTable;
    echo $puzzle->buttons;
} else {
    echo "<h1>An error happend please go back and recheck your puzzle name.</h1>";
    // TODO: re-input name?
}

/*   * step1: steralize input
     * step2: see if puzzle exisits
     * step3: get words
     * step4: get clues
     * step5: generate puzzle
     * step6: gernerate js
     */
function generateExactPuzzle($nameOfPuzzle, $puzzle_id) {
    $words = "";
    $nameEntered = validate_input($nameOfPuzzle);
    $puzzle_id = validate_input($puzzle_id);
    $nameEntered = mb_strtolower($nameOfPuzzle, 'UTF-8');
    // check if the name already exists
    $nameExist = checkPuzzleId($puzzle_id);
    echo "NameofPuzzle" . $nameOfPuzzle;
    if (!$nameExist) {
        generatePuzzle($nameEntered);
    } else { // puzzle exists
        echo $puzzle_chars = getWordChars($nameEntered);
        $word_array = getWordValuesFromPuzzleWords($puzzle_id);
        $clues_array = getClueValuesFromPuzzleWords($puzzle_id);
       // var_dump($word_array);
        //var_dump($clues_array);
        $i = 0;
        foreach ($puzzle_chars as $char) {
            $word_chars = getWordChars($word_array[$i]);

            // this is for building a comma seperate string of the words for the puzzle. For later use in javascript.
            if ($i == 0) {
                $words .= buildJScriptWords($word_chars);
            } else {
                $words .= ',' . buildJScriptWords($word_chars);
            }
            // var_dump($clues_array);
            echo '<tr><td>' . $clues_array[$i] . '</td><td>';
            //$char_indexes = mb_strpos($)//getCharIndex($word_id, $puzzle_name_chars[$i]);
            $wordlen = count($word_chars);

            for ($j = 0; $j < $wordlen; ++$j) {
                if ($char === $word_chars[$j]) {
                    echo '<input class="word_char active" type="text" maxLength="7" value="' . $word_chars[$j] . '" readonly/>';
                } else {
                    echo '<input class="word_char" type="text" maxLength="7" value=""/>';
                }
            }
            echo '</tr>';
            $i++;
        }
        return $words;
    }
}

/**
 * Generates a random puzzle given a name for the puzzle
 * @param  string $nameOfPuzzle name of the puzzle
 * @return string words that are used to display js solution
 */
function generatePuzzle($nameOfPuzzle) {
    $words = "";
    $nameEntered = validate_input($nameOfPuzzle);
    $nameEntered = mb_strtolower($nameOfPuzzle, 'UTF-8');
    //echo "<p>NameEntered: $nameEntered</p>";

    $puzzle_chars = getWordChars($nameEntered);
    $word_array = array();
    $clues_array = array();
    $image_array = array();

    $i = 0;
    foreach ($puzzle_chars as $char) {
        //echo "<p>char: $char</p>";
        $word = get_random_word($char, $word_array);
        if ($word != null) {
            array_push($word_array, getWordValue($word));
            $clueword = getClueFromWord($word);
            array_push($clues_array, $clueword);
            array_push($image_array, getImageName($clueword));

        } else {
            array_push($word_array, $char);
            array_push($clues_array, $char);
            array_push($image_array, getImageName($char));
        }
        $word_chars = getWordChars($word_array[$i]);

        // this is for building a comma seperate string of the words for the puzzle. For later use in javascript.
        if ($i == 0) {
            $words .= buildJScriptWords($word_chars);
        } else {
            $words .= ',' . buildJScriptWords($word_chars);
        }
        //var_dump($clues_array);
        echo '<tr><td>' . $clues_array[$i] . '</td><td>';
        //$char_indexes = mb_strpos($)//getCharIndex($word_id, $puzzle_name_chars[$i]);
        $wordlen = count($word_chars);

        for ($j = 0; $j < $wordlen; ++$j) {
            if ($char === $word_chars[$j]) {
                echo '<input class="word_char active" type="text" maxLength="7" value="' . $word_chars[$j] . '" readonly/>';
            } else {
                echo '<input class="word_char" type="text" maxLength="7" value=""/>';
            }
        }
        echo '</tr>';
        $i++;
    }
    //var_dump($image_array);
    return $words;
}

function createPuzzle($nameEntered) {
    $nameExist = false;
    $words = "";
    // clean up the name entered
    $nameEntered = validate_input($nameEntered);
    $nameEntered = mb_strtolower($nameEntered, 'UTF-8');


    // check if the name already exists
    $nameExist = checkName($nameEntered);
    if (!$nameExist) {
        create_puzzle($nameEntered);
        create_puzzle_words($nameEntered);
    }

    $puzzle_id = getPuzzleId($nameEntered);

    if ($puzzle_id != null) {
        $puzzle_name_chars = getWordChars($nameEntered);
        // get length of puzzle name
        $nameLen = count($puzzle_name_chars);

        // for each character in the puzzle name
        for ($i = 0; $i < $nameLen; ++$i) {
            // get the word_id from the puzzle_words table at position $i in the puzzle name.
            $word_id = getWordId($puzzle_id, $i);

            // then get the word_value of that word_id
            $word_value = getWordValue($word_id);

            // get the character array of the word
            $word_chars = getWordChars($word_value);

            // this is for building a comma seperate string of the words for the puzzle. For later use in javascript.
            if ($i == 0) {
                $words .= buildJScriptWords($word_chars);
            } else {
                $words .= ',' . buildJScriptWords($word_chars);
            }

            // output the clue word of the word (the word_value with the word_id = rep_id of this words)
            $clue_word = getClueWord($word_id);

            // Add clue words to first column of the row
            echo '<tr>
							 <td>' . $clue_word . '</td>
							 <td>';

            $char_indexes = getCharIndex($word_id, $puzzle_name_chars[$i]);
            $wordlen = count($word_chars);

            for ($j = 0; $j < $wordlen; ++$j) {
                if (in_array($j, $char_indexes)) {
                    echo '<input class="word_char active" type="text" maxLength="7" value="' . $word_chars[$j] . '" readonly/>';
                } else {
                    echo '<input class="word_char" type="text" maxLength="7" value=""/>';
                }
            }
            echo '</tr>';
        }
    } else {
        // set name-textbox on index.php to error message that name doesn't exist
        // re
    }
    return $words;
}

// Takes in an array of characters and builds a string by seperating them with '-'. Returns the string.
function buildJScriptWords($word_chars) {
    $string = "";
    $wordLng = count($word_chars);
    for ($i = 0; $i < $wordLng; ++$i) {
        if ($i == 0) {
            $string .= $word_chars[$i];
        } else {
            $string .= '-' . $word_chars[$i];
        }
    }
    return $string;
}

function pullInputFromSave() {
    $input = array();
    $puzzleName = "";
    $word_id_array = array();
    $clue_id_array = array();
    $word = 'word';
    $clue = 'clue';
    $i = 0;
    if (isset($_POST['puzzleWord'])) {
       $puzzleName = mb_strtolower(validate_input($_POST['puzzleWord']), 'UTF-8');
    }

    for ($i = 0; isset($_POST[$word . "" . $i]) && isset($_POST[$clue . "" . $i]); $i++) {
       // echo($_POST[$word . "" . $i]);
        array_push($word_id_array, validate_input($_POST[$word . "" . $i]));
        array_push($clue_id_array, validate_input($_POST[$clue . "" . $i]));
    }
    array_push($input, $puzzleName, $word_id_array, $clue_id_array);
    return $input;
}

function savePuzzle($puzzleName, $word_array, $clue_array){
    // create puzzle
    create_puzzle($puzzleName);
    // create puzzle_words
    $puzzle_id = (getMaxPuzzleId(-1) - 1);
   // echo $puzzleName;
   // var_dump($word_array);
    input_puzzle_words($word_array, $clue_array, $puzzle_id);
}

function getWordIdArray($word_array) {
    $word_id_array = array();
    foreach ($word_array as $word) {
        array_push($word_id_array, getWordIdFromWord($word));
    }
    return $word_id_array;
}

function getClueIdArray($clue_array) {
    $clue_id_array = array();
    foreach ($clue_array as $word) {
        array_push($clue_id_array, getWordIdFromWord($word));
    }
    return $clue_id_array;
}

function getImage($puzzle, $index) {
   // var_dump($puzzle->image_array);
    $image = $puzzle->image_array[$index];
    if (empty($image)) {
        $clue = $puzzle->word_array[$index];
        //echo $image;
        $sql = "SELECT * FROM words where word = '$clue'";
        $result = run_sql($sql);
        $numOfRows = $result->num_rows;
        if ($numOfRows > 0) {
            while ($row = $result->fetch_assoc()) {
                if (!empty($row['image'])) {
                    echo $image = $row['image'];
                    $imageLocation = "./Images/$image";
                    if (file_exists($imageLocation)) {
                        return "./Images/$image";
                    } else {
                        return "./Images/noImage.jpg";
                    }
                }
            }
            return "./Images/noImage.jpg";
        } else {
            return "./Images/noImage.jpg";
        }
    }
    $imageLocation = "./Images/$image";
    if (file_exists($imageLocation)) {
        return "./Images/$image";
    } else {
        return "./Images/noImage.jpg";
    }
}

function tryAgain() {
    echo '<h2>Puzzle name was empty please enter a name</h2><form action="puzzle.php" method="post">
        <div class="container">
          <div class="inputDiv"><input type="textbox" name="puzzleWord" id="name-textbox" placeholder="Enter your Name to see the Puzzle" onclick="clearFields();" />
          </div>
          <br>
          <div style="text-align:center">
            <input class="main-buttons" type="submit" name="randomPlay" value="Show me.." />
          </div>
        </div>
      </form>';
}

function updatePuzzle()
{
    $new_id_array = array();
    $old_id_array = array();
    $image_array = array();
    $new_id = 'newWordId';
    $old_id = 'oldWordId';
    $word_array = array();
    $clue_array = array();
    $i = 0;
    if (isset($_POST['puzzleWord'])) {
        $puzzleName = mb_strtolower(validate_input($_POST['puzzleWord']), 'UTF-8');
        $puzzle = getWordChars($puzzleName);
    }

    for ($i = 0; isset($_POST[$new_id.$i]) && isset($_POST[$old_id.$i]); $i++) {
        if(!empty($_POST[$new_id.$i])) {
            array_push($new_id_array, validate_input($_POST[$new_id.$i]));
        }
        else{
            echo "No new ID for ".($_POST[$old_id.$i]). "was provided. It will not update.";
            array_push($new_id_array, validate_input($_POST[$old_id.$i]));
        }
        array_push($old_id_array, validate_input($_POST[$old_id.$i]));
    }

    for($i=0; $i < count($new_id_array); $i++){
        if(getCharIndex($new_id_array[$i],$puzzle[$i]) === null){
            echo "<script>alert('Word id: $new_id_array[$i] does not contain the right character: $puzzle[$i]. It will not be updated.');</script>";
            $new_id_array[$i] = $old_id_array[$i];
        }
        $word = getWordValue($new_id_array[$i]);
        array_push($word_array, $word);
        $clue = getClue($new_id_array[$i]);
        array_push($clue_array, $clue);
        array_push($image_array, getImageName($word));
    }

  echo "<form method='POST' action='#'>";
    $puzzleName = validate_input($_POST['puzzleWord']);
    $preferedPosition = (int)validate_input($_POST['position']);
    $minLength = (int)validate_input($_POST['minLength']);
    $maxLength = (int)validate_input($_POST['maxLength']);
    $puzzle = new Puzzle($puzzleName, -1, $preferedPosition, $minLength, $maxLength);
    $puzzle->word_array = $word_array;
    $puzzle->clues_array = $clue_array;
    $puzzle->image_array = $image_array;
    $puzzle->wordId_array = $new_id_array;
    echo "'Puzzle: $puzzleName has been updated!";
    $words = $puzzle->js_solution;
    echo $puzzle->createAdminInputBoxes();
    echo $puzzle->admin_buttons;
    echo "</form>";
}
/**
 * Puzzle class that represents a puzzle in the database
 */
class Puzzle {
    /**Constructor that initializes all param's
     * @param string $puzzleName of puzzle
     * @param integer $puzzle_id of puzzle
     * @param integer $preferedPosition of char in synonym must be >= -1
     * @param integer $minLength of synonyms in puzzle must be >= -1
     * @param integer $maxLength of synonyms in puzle must be >= -1
     */
    function Puzzle($puzzleName, $puzzle_id, $preferedPosition, $minLength, $maxLength) {
        try {
            $this->setName($puzzleName);
            $this->setId($puzzle_id);
            $this->setMinmaxLength($minLength, $maxLength);
            $this->setPreferedPosition($preferedPosition);
            $this->setPuzzleWords($puzzleName, $puzzle_id);
            $this->setCharIndexes();
            $this->createJSSolution();
            $this->createInputBoxes();
            $this->createTableFooter();
        } catch (Exception $e) {
            echo 'Message: ' . $e->getMessage();
        }
    }

    /**
     * Sets the name of the puzzle
     * @param string $puzzleName of puzzle
     */
    function setName($puzzleName) {
        if (!is_string($puzzleName)) {
            throw new Exception("Name of a puzzle must be a string!");
        } else if (empty($puzzleName)) {
            throw new Exception("Name of puzzle can't be empty!");
        }
        $this->puzzleName = mb_strtolower($puzzleName, 'UTF-8');
    }

    /**
     * Initializes puzzle_id to param if the puzzle does exits
     * @param integer $puzzle_id of puzzle
     */
    function setId($puzzle_id) {
        $this->puzzle_id = -1;
        if (!is_int($puzzle_id)) {
            throw new Exception("Puzzle_Id must be an integer!");
        } else if ($puzzle_id !== -1 && !checkPuzzleId($puzzle_id)) {
            throw new Exception("Puzzle_Id did not find a match!");
        }
        $this->puzzle_id = $puzzle_id;
    }

    function setMinmaxLength($minLength, $maxLength) {
        if (!is_int($minLength) || !is_int($maxLength)) {
            throw new Exception("Puzzle length must be an integer!");
        } else if ($minLength < 0 || $maxLength < 0) {
            throw new Exception("Puzzle length must greater than 0!");
        } else {
            $this->minLength = $minLength;
            $this->maxLength = $maxLength;
        }
    }

    /**
     * Sets the prefered position of the char in synonyms
     * @param integer $preferedPosition of char in synonyms
     */
    function setPreferedPosition($preferedPosition) {
        if (!is_int($preferedPosition)) {
            throw new Exception("Position must be an integer");
        } else if ($preferedPosition < -1) {
            throw new Exception("Position out of bounds");
        }
        $this->position = $preferedPosition;
    }

    /**
     * breaks name entered into chars
     * if puzzle exisits get words and clue
     * else create random words and clue
     * then saves them to the object
     */
    function setPuzzleWords() {
        $word_array = array();
        $clues_array = array();
        $image_array = array();
        $wordId_array = array();
        $puzzle_chars = getWordChars($this->puzzleName);
        foreach ($puzzle_chars as $char) {
            $index = getWordIdFromChar($char, $this->position, $this->minLength, $this->maxLength);
            if ($this->puzzle_id !== -1) {
                // echo($this->puzzle_id);
                $word_array = getWordValuesFromPuzzleWords($this->puzzle_id);
                // var_dump($word_array);
                $clues_array = getClueValuesFromPuzzleWords($this->puzzle_id);
                $wordId_array = getWordValuesFromPuzzleWords($this->puzzle_id);
              //  var_dump($word_array);
                foreach ($word_array as $value) {
                    array_push($image_array, getImageName($value));
                }
                break;
            }
            if ($index != false) {
                $wordVal = getWordValue($index);
                $wordId = getWordIdFromWord($wordVal);
                array_push($word_array, $wordVal);
                array_push($wordId_array, $wordId);
                $clueword = getRandomClueWord($index);
                array_push($clues_array, $clueword);
                array_push($image_array, getImageName($wordVal));
            } else {
                array_push($word_array, $char);
                array_push($clues_array, $char);
                array_push($image_array, $char);
            }
        }
        $this->puzzle_chars = $puzzle_chars;
        $this->wordId_array = $wordId_array;
        $this->word_array = $word_array;
        $this->clues_array = $clues_array;
        $this->image_array = $image_array;
    }

    /**
     * Creates comma delimited list to be used in show solution
     */
    function createJSSolution() {
        $words = "";
        $i = 0;
        foreach ($this->puzzle_chars as $char) {
            $word_chars = getWordChars($this->word_array[$i]);

            // this is for building a comma separate string of the words for the puzzle. For later use in javascript.
            if ($i == 0) {
                $words .= buildJScriptWords($word_chars);
            } else {
                $words .= ',' . buildJScriptWords($word_chars);
            }
            $i++;
        }
        $this->js_solution = $words;
    }

    /**
     * Creates html for body of table for user to input synonyms
     */
    function createInputBoxes() {
        $htmlTable = '<div class="container"><h1 style="color:red;">"' . $this->puzzleName . '"</h1><table class="table table-condensed main-tables" id="puzzle_table" ><thead><tr><th>Clue</th><th>Image</th><th>Word</th></tr></thead><tbody>';
        $i = 0;
        foreach ($this->puzzle_chars as $puzzleChar) {
            $word_chars = getWordChars($this->word_array[$i]);
            $pos = array_search($puzzleChar, $word_chars) + 1;
            $len = count($word_chars);
            $htmlTable .= "<tr><td align='center' style='vertical-align: middle;'>" . $pos . '/' . $len . "</td>";
            $image = getImage($this, $i);
            if (strcasecmp($image, "./Images/noImage.jpg") === 0) {
                $htmlTable .= "<td>" . $puzzleChar . "</td><td>";
            } else {
                //echo $image;
                $htmlTable .= "<td><img class=\"thumbnailSize\" src=" . $image . " alt =" . $image . "></td><td style='vertical-align: middle;'>";
            }
            $htmlTable .= '<input class="altPuzzleInput active" type="text" maxLength="7" value="' . $puzzleChar . '" style="display:none;" readonly/><input class="altPuzzleInput" type="text" value="" style="display:none;"/>';
            $flag = false;
            for ($j=0; $j <count($word_chars); $j++) {
                if (($j === ($pos - 1)) && !$flag) {
                    // $htmlTable .= '<input class="puzzleInput word_char active" type="text" maxLength="7" style="display:inline"/>';
                    $htmlTable .= '<input class="puzzleInput word_char active" type="text" maxLength="7" value="' . $word_chars[$j] . '" style="display:inline" readonly/>';
                    $flag = true;
                } else {
                    $htmlTable .= '<input class="puzzleInput word_char" type="text" maxLength="7" value="" style="display:inline"/>';
                }
            }

            $htmlTable .= "</div>";
            $i++;
        }
        $htmlTable .= '</tbody></table><img id="success_photo" class="success" src="pic/thumbs_up.png" alt="Success!" style="display:none"></div>';
        $this->htmlTable = $htmlTable;
    }

    function createAdminInputBoxes() {
        $htmlTable = '<div class="container"><h1 style="color:red;">"' . $this->puzzleName . '"</h1><table class="table table-condensed main-tables" id="puzzle_table" style="width:100%"><thead><tr><th>Word Id</th><th>Image</th><th>Index</th><th>English Word</th><th>Word</th></tr></thead><tbody>';
        $i = 0;
        foreach ($this->puzzle_chars as $puzzleChar) {
            $word_chars = getWordChars($this->word_array[$i]);
            //  $htmlTable .= "<tr><td>".$this->wordId_array[$i]."</td>";
            $id = $this->wordId_array[$i];
            $htmlTable .= '<tr><td>
                        <input class="puzzleInput word_char" type="text" name="newWordId'.$i.'" placeholder=" ' . $id . ' "/>
                        <input type="hidden" name="oldWordId'.$i.'" value="' .$id. '"/>
                        </td>';

            $image = getImage($this, $i);
            if (strcasecmp($image, "./Images/noImage.jpg") === 0) {
                $htmlTable .= "<td>" . $puzzleChar . "</td><td>";
            } else {
                //echo $image;
                $htmlTable .= "<td><img class=\"thumbnailSize\" src=" . $image . " alt =" . $image . "></td><td style='vertical-align: middle;'>";
            }

            $pos = array_search($puzzleChar, $word_chars) + 1;
            $len = count($word_chars);
            $htmlTable .= "<td>" . $pos . '/' . $len . "</td>";
            $htmlTable .= "<td>" . $this->clues_array[$i] . "<input type='hidden' name='clue" . $i . "' value='" . $this->clues_array[$i] . "'/></td>";
            $htmlTable .= "<td>" . $this->word_array[$i] . "<input type='hidden' name='word" . $i . "' value='" . $this->word_array[$i] . "'/></td></tr></form>";
            $flag = false;
            for ($j=0; $j <count($word_chars); $j++) {
                if (($j === ($pos - 1)) && !$flag) {
                    // $htmlTable .= '<input class="puzzleInput word_char active" type="text" maxLength="7" style="display:inline"/>';
                    $htmlTable .= '<input class="puzzleInput word_char active" type="text" maxLength="7" value="' . $word_chars[$j] . '" style="display:inline" readonly/>';
                    $flag = true;
                } else {
                    $htmlTable .= '<input class="puzzleInput word_char" type="text" maxLength="7" value="" style="display:inline"/>';
                }
            }
            $htmlTable .= "</div>";
            $i++;
        }
        $htmlTable .= '</tbody></table></div>';
        return $htmlTable;
    }

    /**
     * Sets the char indexes where the char in puzzle_chars can
     * be found in the synonyms
     */
    function setCharIndexes()
    {
        $char_indexes = array();
        $i = 0;
        foreach ($this->puzzle_chars as $char) {
            $word_chars = getWordChars($this->word_array[$i]);
            $j = 0;
            foreach ($word_chars as $char2) {
                if ($char === $char2) {
                    array_push($char_indexes, $j + 1);
                    break;
                }
                $j++;
            }
            $i++;
            $j = 0;
        }
        $this->char_indexes = $char_indexes;
    }

    // TODO: creates a puzzle in db based on puzzle object info
    function createPuzzleInDB()
    {
        // create puzzle
        // get word_id_array
        // get clue_id_array
        $puzzleName = $this->puzzleName;
        // $word_id_array = getWordIdArray($this->word_array);
        //$clue_id_array = getClueIdArray($this->clues_array);
        savePuzzle($puzzleName, $this->word_array, $this->clues_array);
    }

    function createTableFooter()
    {
        $this->buttons = '<div class="container" ><input class="main-buttons" type="button" name="submit_solution" 
                value="Submit Solution" onclick="main_buttons(\'submit\');">
      ' . getShowSolution($this->puzzleName) . '<input class="main-buttons" type="button" name="changeInputMode" 
                value="Change Input Mode" onclick="change_puzzle_input()"></div>';
        $this->admin_buttons = '<div class="container"><input class="word_char active" type="hidden" maxLength="2" name="minLength" value="' . $this->minLength . '" 
                style="display:inline"/><input class="word_char active" type="hidden" maxLength="2" name="maxLength" 
                value="' . $this->maxLength . '" style="display:inline"/><input class="word_char active" type="hidden" 
                name="puzzleWord" value="' . $this->puzzleName . '"/> <input 
                class="word_char active" type="hidden" maxLength="2" name="position" value="' . $this->position . '" style="display:inline"/>
                <div style="text-align:center">
                <input class="main-buttons" type="submit" name="UpdateImages" value="Update Images"/>
                <input class="main-buttons" type="submit" name="iDesign" value="Refresh"/>
                <input class="main-buttons" type="submit" name="saveIDesign" value="Save"/></div></div>';
    }
}

?>

<script type="text/javascript">
    /**
     *     main function for the buttons when they're clicked.
     */
    function main_buttons(button_name){
        // the words should be seperated by commas and the characters of the words by '-'.
        var words = "<?php echo $words ?>";
        var wordsArray = words.split(",");
        // get the table and it's length.
        var table = document.getElementById("puzzle_table");
        var tableLength = table.rows.length;
        // helper variables.
        var words_correct = true;
        var childrenLength = 0;

        // start at 1 because top row for the table is the header of the table.
        for (var i = 1; i < tableLength; i++) {
            // for submit_solution
            if (button_name == "submit") {
                // call submit_validation handler method for the submit solution button
                words_correct = submit_validation(table, wordsArray[i - 1], i);

                // break out of loop. If the next words is the last words and the user guessed it right,
                // then the words_correct would end up as true, even if one words was false.
                if (words_correct === false) {
                    break;
                }
            } else if (button_name == "show") { // for show solution
                // call show_solution handler method for the show solution button
                show_solution(table, wordsArray[i - 1], i);
            }
        }

        if (button_name == "submit") {
            // checks if the words are correct by passing in words_correct boolean flag.
            checkCorrect(words_correct);
        }
    }

    //validation the word
    function submit_validation(table, word, i) {
        var input_word = "";
        var alt_input_word = "";
        var theWord = rebuildWord(word);
        var childrenLength = table.rows[i].cells[2].children.length;

        alt_input_word += table.rows[i].cells[2].children[1].value;
        for (var j = 0; j < childrenLength - 2; j++) {
            var k = j + 2;
            input_word += table.rows[i].cells[2].children[k].value;
        }

        if (theWord != input_word && theWord != alt_input_word) {
            return false;
        } else {
            return true;
        }
    }

    // rebuild the words whose charactes are seperated by "-".
    function rebuildWord(word) {
        var built_word = "";
        var word_characters = word.split("-");
        var array_length = word_characters.length;

        for (var i = 0; i < array_length; ++i) {
            built_word += word_characters[i];
        }
        return built_word;
    }

    function checkCorrect(words_correct) {
        if (words_correct){ // success case
            //alert("Sucess!");
            var el = document.getElementById("success_photo");
            el.style.display = "inline";
        } else { // failure case
            var el = document.getElementById("pop_up_fail");
            el.style.display = "block";
            clear_puzzle();
        }
    }

    // displays the characters of the current words in the puzzle table from the for loop in main_buttons function.
    function show_solution(table, word, i) {
        var childrenLength = 0;
        var word_array = null;
        var nWord = word;

        word_array = nWord.split("-");
        childrenLength = table.rows[i].cells[2].children.length;
        if (table.rows[i].cells[2].children[1].value.length > 0) {
            clear_puzzle();
        }
        for (var j = 0; j < word_array.length; j++) {
            table.rows[i].cells[2].children[1].value += word_array[j];
        }
        for (var j = 0; j < childrenLength - 2; j++) {
            var k = j + 2;
            table.rows[i].cells[2].children[k].value = word_array[j];
        }
    }

    // clears the character values for all of the words in the puzzle table.
    function clear_puzzle() {
        var table = document.getElementById("puzzle_table");
        var tableLength = table.rows.length;
        var childrenLength = 0;

        for (var i = 1; i < tableLength; i++) {
            childrenLength = table.rows[i].cells[2].children.length;
            for (var j = 0; j < childrenLength; j++) {
                if (!(table.rows[i].cells[2].children[j].className.includes("active"))) {
                    table.rows[i].cells[2].children[j].value = "";
                }
            }
        }
    }

    function change_display_none(o) {
        var el = document.getElementById(o);
        el.style.display = "none";
    }

    function toggle_display(el) {
        if (el.style.display == "inline") {
            el.style.display = "none";
        } else {
            el.style.display = "inline";
        }
    }

    function change_puzzle_input() {
        var alt = document.getElementsByClassName("altPuzzleInput");
        var i;
        for (i = 0; i < alt.length; i++) {
            toggle_display(alt[i]);
        }
        var norm = document.getElementsByClassName("puzzleInput");
        var i;
        for (i = 0; i < norm.length; i++) {
            toggle_display(norm[i]);
        }
    }

//    function changeWordInPuzzle(newId) {
//        alert("Here: " + newId);
//        var puzzle = "<?php //echo $puzzle?>//";
//        var words = "<?php //echo $words ?>//";
//        var table = document.getElementById("puzzle_table");
//        var newid = document.getElementById("id").value;
//        var tableLength = table.rows.length;
//       // table.row[]
//    }
</script>
</body>

</html>
