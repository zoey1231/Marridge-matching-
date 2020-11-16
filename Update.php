
<html>
    <head>
        <title>Insert Request</title>
        <h1> </h1>
    </head>

    <body>

        <h2>Update Customer's information</h2>
        <p>The values are case sensitive and if you enter in the wrong case, the update statement will not do anything.</p>

        <form method="POST" action="Update.php"> <!--refresh page when submitted-->
            <input type="hidden" id="updateQueryRequest" name="updateQueryRequest">
            MemberID: <input type="text" name="memberID"> <br /><br />
            New Name: <input type="text" name="newName"> <br /><br />

            <input type="submit" value="Update" name="updateSubmit"></p>
        </form>

        <hr />

        <h2>Display the Tuples in Customer_advises Table</h2>
        <form method="POST" action="Update.php"> <!--refresh page when submitted-->
            <input type="submit" id="displayTupleRequest" name="displayTupleRequest">
    
        </form>
        <?php
        //this tells the system that it's no longer just parsing html; it's now parsing PHP

        $success = True; //keep track of errors so it redirects the page only if there are no errors
        $db_conn = NULL; // edit the login credentials in connectToDB()
        $show_debug_alert_messages = False; // set to True if you want alerts to show you which methods are being triggered (see how it is used in debugAlertMessage())

        function debugAlertMessage($message) {
            global $show_debug_alert_messages;

            if ($show_debug_alert_messages) {
                echo "<script type='text/javascript'>alert('" . $message . "');</script>";
            }
        }

        function executePlainSQL($cmdstr) { //takes a plain (no bound variables) SQL command and executes it
            //echo "<br>running ".$cmdstr."<br>";
            global $db_conn, $success;

            $statement = OCIParse($db_conn, $cmdstr);
            //There are a set of comments at the end of the file that describe some of the OCI specific functions and how they work

            if (!$statement) {
                echo "<br>Cannot parse the following command: " . $cmdstr . "<br>";
                $e = OCI_Error($db_conn); // For OCIParse errors pass the connection handle
                echo htmlentities($e['message']);
                $success = False;
            }

            $r = OCIExecute($statement, OCI_DEFAULT);
            if (!$r) {
                echo "<br>Cannot execute the following command: " . $cmdstr . "<br>";
                $e = oci_error($statement); // For OCIExecute errors pass the statementhandle
                echo htmlentities($e['message']);
                $success = False;
            }

            return $statement;
        }

        function executeBoundSQL($cmdstr, $list) {
            /* Sometimes the same statement will be executed several times with different values for the variables involved in the query.
        In this case you don't need to create the statement several times. Bound variables cause a statement to only be
        parsed once and you can reuse the statement. This is also very useful in protecting against SQL injection.
        See the sample code below for how this function is used */

            global $db_conn, $success;
            $statement = OCIParse($db_conn, $cmdstr);

            if (!$statement) {
                echo "<br>Cannot parse the following command: " . $cmdstr . "<br>";
                $e = OCI_Error($db_conn);
                echo htmlentities($e['message']);
                $success = False;
            }

            foreach ($list as $tuple) {
                foreach ($tuple as $bind => $val) {
                    //echo $val;
                    //echo "<br>".$bind."<br>";
                    OCIBindByName($statement, $bind, $val);
                    unset ($val); //make sure you do not remove this. Otherwise $val will remain in an array object wrapper which will not be recognized by Oracle as a proper datatype
                }

                $r = OCIExecute($statement, OCI_DEFAULT);
                if (!$r) {
                    echo "<br>Cannot execute the following command: " . $cmdstr . "<br>";
                    $e = OCI_Error($statement); // For OCIExecute errors, pass the statementhandle
                    echo htmlentities($e['message']);
                    echo "<br>";
                    $success = False;
                }
            }
        }

        function printResult($result) { //prints results from a select statement
            echo "<br>Retrieved data from table Customer_advises:<br>";
            echo "<table>";
            echo "<tr><th>MemberID</th><th>Occupation</th><th>Birthday</th><th>Age</th><th>Customer Name</th><th>Acess to other customer's profile</th><th>Designated matchmaker's EmpolyeeID</th></tr>";

            while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
               echo "<tr><td>" . $row["MemberID"] . "</td><td>" . $row["Occupation"] 
               ."</td><td>" . $row["Birthday"] ."</td><td>" . $row["Age"]
               ."</td><td>" . $row["C_name"]."</td><td>" . $row["AccessToOthersProfile"]
               ."</td><td>" . $row["EmpolyeeID"] ."</td></tr>"; 
            }

            echo "</table>";
        }

        function connectToDB() {
            global $db_conn;

            // Your username is ora_(CWL_ID) and the password is a(student number). For example,
            // ora_platypus is the username and a12345678 is the password.
            // $db_conn = OCILogon("ora_lyuchenh", "a95094207", "dbhost.students.cs.ubc.ca:1522/stu");
            $db_conn = OCILogon("ora_zhuoyil", "a37859600", "dbhost.students.cs.ubc.ca:1522/stu");
            if ($db_conn) {
                debugAlertMessage("Database is Connected");
                return true;
            } else {
                debugAlertMessage("Cannot connect to Database");
                $e = OCI_Error(); // For OCILogon errors pass no handle
                echo htmlentities($e['message']);
                return false;
            }
        }

        function disconnectFromDB() {
            global $db_conn;

            debugAlertMessage("Disconnect from Database");
            OCILogoff($db_conn);
        }


        function handleResetRequest() {
            global $db_conn;
            // Drop old table
            executePlainSQL("DROP TABLE Has_Manager");

            // Create new table
            echo "<br> creating new table <br>";
            executePlainSQL("CREATE TABLE Has_Manager (ManagerID int PRIMARY KEY, Name_MSC char(20) NOT NULL,CEO char(20) NOT NULL,Name_m char(20),Workforce int)");
            OCICommit($db_conn);
        }

        function handleUpdateRequest() {
            global $db_conn;

            // need the wrap the old name and new name values with single quotations
            executePlainSQL("UPDATE Customer_advises SET C_name = '{$_POST['newName']}' WHERE MemberID = {$_POST['memberID']}");
            OCICommit($db_conn);
        }
            
        function handledisplayRequest() {
            global $db_conn;

            $result = executePlainSQL("SELECT * FROM Customer_advises");
            printResult($result);
                
        }
        
        if (isset($_POST['updateSubmit'])) {
            if(connectToDB()) {
                handleUpdateRequest();
                disconnectFromDB();
            } 
        } else if (isset($_GET['displayTupleRequest'])) {
            if(connectToDB()) {
                handledisplayRequest();
                disconnectFromDB();
            }
        } else if (isset($_POST['DEMO_redirect'])) {
            header('Location: https://www.students.cs.ubc.ca/~maxonzz/military-system/demo_page.php');
            exit;
        } 

        ?>
    </body>
</html>