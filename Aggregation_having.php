
<html>
    <style>
        head {
            font-size:40px;
        }
        body{
        background-image: url('background_ocean.png');
        background-repeat: no-repeat;
        background-attachment: fixed;
        background-size: 100% 100%;
        }
    </style>

    <head >
        <title>CPSC 304 PHP/Oracle Demonstration</title>
         <h1>Aggregation with Having Request</h1>
    </head>


    <body>
        <h2>Aggregation with Having on Customer_advises table</h2>
        <form method="GET" action="Aggregation_having.php">
            <input type="hidden" id="requestAgg" name="requestAgg">
            <p>Find the maximum age for each occupation only for those occupations that have at least 2 tuples in Customer_advises table :</br> 
            <input type="submit" value="submit" name="clickAggHaving"></p>
        </form>

        <hr/>
        <h2>Display the Tuples in Customer_advises Table</h2>
        <form method="GET" action="Aggregation_having.php"> <!--refresh page when submitted-->
            <input type="submit" value="displayTuples" name="displayTupleRequest">
        </form>

        <hr/>
        <form method="POST" action="demo_page.php"> <!--refresh page when submitted-->
            <input type="submit" value="BACK TO MAIN PAGE" name="DEMO_redirect"></p>
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
            echo" <hr/>";
            echo"<h2>Result</h2>";
            echo "<br>Customers' occupations that their minimum age is strictly below the average of the minimum age over all occupations where there have more than one person: <br> is(are): ";
            echo "<table>";
            echo "<tr><th>Occupation </th><th>Maximum Age </th></tr>";

            while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
                echo "<tr><td>" . $row[0] . "</td><td>" . $row[1] . "</td></tr>";
                // echo $row[0];
            }
            echo "</table>";
        }
        function printCustomer_advises($result) { //prints results from a select statement
            echo" <hr/>";
            echo "<br>Retrieved data from table Customer_advises:<br>";
            echo "<table>";
            echo "<tr><th>MemberID </th><th>Occupation </th><th>Birthday </th><th>Age </th><th>Customer Name </th><th>AccessToOthersProfile </th><th>Designated matchmaker's EmpolyeeID </th></tr>";

            while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
               echo "<tr><td>" .$row[0]. "</td><td>" .$row[1].
               "</td><td>" .$row[2]."</td><td>" .$row[3].
               "</td><td>" .$row[4]."</td><td>" .$row[5].
               "</td><td>" .$row[6]."</td></tr>";
            }

            echo "</table>";
        }
        function connectToDB() {
            global $db_conn;

            // Your username is ora_(CWL_ID) and the password is a(student number). For example,
            // ora_platypus is the username and a12345678 is the password.
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

        function handleAggHaving() {
            global $db_conn;
            $result = executePlainSQL("SELECT Occupation,MAX(age) AS maxage
                                        FROM Customer_advises
                                        GROUP BY Occupation
                                        HAVING COUNT(*)>1");

            printResult($result);
        }
        function handledisplayRequest() {
            global $db_conn;
            $result = executePlainSQL("SELECT * FROM Customer_advises");
            printCustomer_advises($result);
                
        }
        if (isset($_GET['clickAggHaving'])) {
            if(connectToDB()) {
                handleAggHaving();
                disconnectFromDB();
            }
        } else if (isset($_GET['displayTupleRequest'])) {
             if(connectToDB()) {
                handledisplayRequest();
                disconnectFromDB();
            }
        } else if (isset($_POST['DEMO_redirect'])) {
            header('Location: https://www.students.cs.ubc.ca/~zhuoyil/Marridge-matching-/demo_page.php');
            exit;
        }
        ?>
    </body>
</html>
