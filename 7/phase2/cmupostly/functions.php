<?php

include "config.php";

/*
 * For all functions $dbh is a database connection
 */

/*
 * @return handle to database connection
 */
function db_connect($host, $port, $db, $user, $pw) {
    $conn_str = sprintf("host=%s port=%d dbname=%s user=%s password=%s", $host, $port, $db, $user, $pw);
    $dbh = pg_connect($conn_str) or die('connection failed');
    return $dbh;
}

/*
 * Close database connection
 */ 
function close_db_connection($dbh) {
    pg_close($dbh);
}

/*
 * Login if user and password match
 * Return associative array of the form:
 * array(
 *		'status' =>  (1 for success and 0 for failure)
 *		'userID' => '[USER ID]'
 * )
 */
function login($dbh, $user, $pw) {
    $sql = pg_prepare($dbh, "login", "SELECT * FROM cmu_user WHERE username = $1 AND password = $2");
    $sql = pg_execute($dbh, "login", array($user, $pw));

    if (!$sql || pg_num_rows($sql) == 0) {
        return array("status" => 0);
    }
    else {
        $row = pg_fetch_array($sql);
        return array("status" => 1, "userID" => $row["username"]);
    }
}

/*
 * Register user with given password 
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'userID' => '[USER ID]'
 * )
 */
function register($dbh, $user, $pw) {
    $sql1 = pg_prepare($dbh, "check_user_exist", "SELECT * FROM cmu_user WHERE username = $1");
    $sql1 = pg_execute($dbh, "check_user_exist", array($user));

    // if the username is available
    if ($sql1 && pg_num_rows($sql) == 0) {
        $sql2 = pg_prepare($dbh, "register", "INSERT INTO cmu_user VALUES ($1, $2)");
        $sql2 = pg_execute($dbh, "register", array($user, $pw));
        if ($sql2) {
            return array("status" => 1, "userID" => $user);
        }
    }
    return array("status" => 0);
}

/*
 * Register user with given password 
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 * )
 */
function post_post($dbh, $title, $msg, $me) {
    $sql = pg_prepare($dbh, "add_post", "INSERT INTO cmu_post(title, content, username, create_time) VALUES($1, $2, $3, $4)");
    $sql = pg_execute($dbh, "add_post", array($title, $msg, $me, time()));

    if (!$sql) {
        return array("status" => 0);
    }
    else {
        return array("status" => 1);
    }
}


/*
 * Get timeline of $count most recent posts that were written before timestamp $start
 * For a user $user, the timeline should include all posts.
 * Order by time of the post (going backward in time), and break ties by sorting by the username alphabetically
 * Return associative array of the form:
 * array(
 *		'status' => (1 for success and 0 for failure)
 *		'posts' => [ (Array of post objects) ]
 * )
 * Each post should be of the form:
 * array(
 *		'pID' => (INTEGER)
 *		'username' => (USERNAME)
 *		'title' => (TITLE OF POST)
 *    'content' => (CONTENT OF POST)
 *		'time' => (UNIXTIME INTEGER)
 * )
 */
function get_timeline($dbh, $user, $count = 10, $start = PHP_INT_MAX) {
    $sql = pg_prepare($dbh, "get_timeline", "SELECT * FROM cmu_post WHERE create_time < $1 ORDER BY create_time DESC, username LIMIT $2");
    $sql = pg_execute($dbh, "get_timeline", array($start, $count));

    if (!$sql) {
        return array("status" => 0);
    }
    else {
        $result = array();
        while ($row = pg_fetch_array($sql)) {
            $new_post = array("pID" => $row["post_id"],
                              "username" => $row["username"],
                              "title" => $row["title"],
                              "content" => $row["content"],
                              "time" => $row["create_time"]);
            array_push($result, $new_post);
        }
        return array("status" => 1, "posts" => $result);
    }
}

/*
 * Get list of $count most recent posts that were written by user $user before timestamp $start
 * Order by time of the post (going backward in time)
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'posts' => [ (Array of post objects) ]
 * )
 * Each post should be of the form:
 * array(
 *		'pID' => (INTEGER)
 *		'username' => (USERNAME)
 *		'title' => (TITLE)
 *		'content' => (CONTENT)
 *		'time' => (UNIXTIME INTEGER)
 * )
 */
function get_user_posts($dbh, $user, $count = 10, $start = PHP_INT_MAX) {
    $user_exist = pg_prepare($dbh, "user_exist", "SELECT * FROM cmu_user WHERE username = $1");
    $user_exist = pg_execute($dbh, "user_exist", array($user));

    if (!$user_exist || pg_num_rows($user_exist) == 0) {
        return array("status" => 0);
    }
    
    $sql = pg_prepare($dbh, "get_timeline", "SELECT * FROM cmu_post WHERE create_time < $1 AND username = $2 ORDER BY create_time DESC LIMIT $3");
    $sql = pg_execute($dbh, "get_timeline", array($start, $user, $count));

    if (!$sql) {
        return array("status" => 0);
    }
    else {
        $result = array();
        while ($row = pg_fetch_array($sql)) {
            $new_post = array("pID" => $row["post_id"],
                              "username" => $row["username"],
                              "title" => $row["title"],
                              "content" => $row["content"],
                              "time" => $row["create_time"]);
            array_push($result, $new_post);
        }
        return array("status" => 1, "posts" => $result);
    }
}

/*
 * Deletes a post given $user name and $pID.
 * $user must be the one who posted the post $pID.
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success. 0 or 2 for failure)
 * )
 */
function delete_post($dbh, $user, $pID) {
    $user_exist = pg_prepare($dbh, "user_exist", "SELECT * FROM cmu_user WHERE username = $1");
    $user_exist = pg_execute($dbh, "user_exist", array($user));

    if (!$user_exist || pg_num_rows($user_exist) == 0) {
        return array("status" => 0);
    }
    
    $post_exist = pg_prepare($dbh, "post_exist", "SELECT * FROM cmu_post WHERE post_id = $1");
    $post_exist = pg_execute($dbh, "post_exist", array($pID));

    if (!$post_exist || pg_num_rows($post_exist) == 0) {
        return array("status" => 0);
    }
    
    $sql1 = pg_prepare($dbh, "is_owner", "SELECT * FROM cmu_post WHERE post_id = $1 AND username = $2");
    $sql1 = pg_execute($dbh, "is_owner", array($pID, $user));

    if ($sql1) {
        if (pg_num_rows($sql1) == 0) {
            return array("status" => 2);
        }
        else {
            $sql2 = pg_prepare($dbh, "delete_post_likes", "DELETE FROM cmu_likes WHERE post_id = $1");
            $sql2 = pg_execute($dbh, "delete_post_likes", array($pID));
            $sql3 = pg_prepare($dbh, "delete_post", "DELETE FROM cmu_post WHERE post_id = $1");
            $sql3 = pg_execute($dbh, "delete_post", array($pID));
            if ($sql2 && $sql3) {
                return array("status" => 1);
            }
        }
    }
    return array("status" => 0);
}

/*
 * Records a "like" for a post given logged-in user $me and $pID.
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success. 0 for failure)
 * )
 */
function like_post($dbh, $me, $pID) {
    $post_exist = pg_prepare($dbh, "post_exist", "SELECT * FROM cmu_post WHERE post_id = $1");
    $post_exist = pg_execute($dbh, "post_exist", array($pID));

    if (!$post_exist || pg_num_rows($post_exist) == 0) {
        return array("status" => 0);
    }
    
    $sql = pg_prepare($dbh, "is_owner", "SELECT * FROM cmu_post WHERE post_id = $1 AND username = $2");
    $sql = pg_execute($dbh, "is_owner", array($pID, $me));
    if (!$sql || pg_num_rows($sql) != 0) {
        return array("status" => 0);
    }

    $sql = pg_prepare($dbh, "likes", "INSERT INTO cmu_likes VALUES ($1, $2)");
    $sql = pg_execute($dbh, "likes", array($me, $pID));

    if (!$sql) {
        return array("status" => 0);
    } else {
        return array("status" => 1);
    }
}

/*
 * Check if $me has already liked post $pID
 * Return true if user $me has liked post $pID or false otherwise
 */
function already_liked($dbh, $me, $pID) {
    $post_exist = pg_prepare($dbh, "post_exist", "SELECT * FROM cmu_post WHERE post_id = $1");
    $post_exist = pg_execute($dbh, "post_exist", array($pID));

    if (!$post_exist || pg_num_rows($post_exist) == 0) {
        return false;
    }
    
    $sql = pg_prepare($dbh, "already_liked", "SELECT * FROM cmu_likes WHERE username = $1 AND post_id = $2");
    $sql = pg_execute($dbh, "already_liked", array($me, $pID));

    if (!$sql || pg_num_rows($sql) == 0) {
        return false;
    }
    else {
        return true;
    }
}

/*
 * Find the $count most recent posts that contain the string $key
 * Order by time of the post and break ties by the username (sorted alphabetically A-Z)
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'posts' => [ (Array of Post objects) ]
 * )
 */
function search($dbh, $key, $count = 50) {
    $sql = pg_prepare($dbh, "search_post", "SELECT * FROM cmu_post WHERE content LIKE $1 OR title LIKE $2 ORDER BY create_time DESC, username LIMIT $3");
    $sql = pg_execute($dbh, "search_post", array("%".$key."%", "%".$key."%", $count));

    if (!$sql) {
        return array("status" => 0);
    }
    else {
        $result = array();
        while ($row = pg_fetch_array($sql)) {
            $new_post = array("pID" => $row["post_id"],
                              "username" => $row["username"],
                              "title" => $row["title"],
                              "content" => $row["content"],
                              "time" => $row["create_time"]);
            array_push($result, $new_post);
        }
        return array("status" => 1, "posts" => $result);
    }
}

/*
 * Find all users whose username includes the string $name
 * Sort the users alphabetically (A-Z)
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'users' => [ (Array of user IDs) ]
 * )
 */
function user_search($dbh, $name) {
    $sql = pg_prepare($dbh, "search_user", "SELECT username FROM cmu_user WHERE username LIKE $1 ORDER BY username");
    $sql = pg_execute($dbh, "search_user", array("%".$name."%"));
    
    if (!$sql) {
        return array("status" => 0);
    }
    else {
        $result = array();
        while ($row = pg_fetch_array($sql)) {
            array_push($result, $row["username"]);
        }
        return array("status" => 1, "users" => $result);
    }
}


/*
 * Get the number of likes of post $pID
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'count' => (The number of likes)
 * )
 */
function get_num_likes($dbh, $pID) {
    $post_exist = pg_prepare($dbh, "post_exist", "SELECT * FROM cmu_post WHERE post_id = $1");
    $post_exist = pg_execute($dbh, "post_exist", array($pID));

    if (!$post_exist || pg_num_rows($post_exist) == 0) {
        return array("status" => 0);
    }
    
    $sql = pg_prepare($dbh, "get_num_likes", "SELECT count(*) FROM cmu_likes WHERE post_id = $1");
    $sql = pg_execute($dbh, "get_num_likes", array($pID));

    if (!$sql) {
        return array("status" => 0);
    }
    else {
        $row = pg_fetch_array($sql);
        return array("status" => 1, "count" => $row["count"]);
    }
}

/*
 * Get the number of posts of user $uID
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'count' => (The number of posts)
 * )
 */
function get_num_posts($dbh, $uID) {
    $user_exist = pg_prepare($dbh, "user_exist", "SELECT * FROM cmu_user WHERE username = $1");
    $user_exist = pg_execute($dbh, "user_exist", array($uID));

    if (!$user_exist || pg_num_rows($user_exist) == 0) {
        return array("status" => 0);
    }

    $sql = pg_prepare($dbh, "get_num_posts", "SELECT count(*) FROM cmu_post WHERE username = $1");
    $sql = pg_execute($dbh, "get_num_posts", array($uID));

    if (!$sql) {
        return array("status" => 0);
    }
    else {
        $row = pg_fetch_array($sql);
        return array("status" => 1, "count" => $row["count"]);
    }
}

/*
 * Get the number of likes user $uID made
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'count' => (The number of likes)
 * )
 */
function get_num_likes_of_user($dbh, $uID) {
    $user_exist = pg_prepare($dbh, "user_exist", "SELECT * FROM cmu_user WHERE username = $1");
    $user_exist = pg_execute($dbh, "user_exist", array($uID));

    if (!$user_exist || pg_num_rows($user_exist) == 0) {
        return array("status" => 0);
    }

    $sql = pg_prepare($dbh, "get_num_likes_user", "SELECT count(*) FROM cmu_likes WHERE username = $1");
    $sql = pg_execute($dbh, "get_num_likes_user", array($uID));

    if (!$sql) {
        return array("status" => 0);
    }
    else {
        $row = pg_fetch_array($sql);
        return array("status" => 1, "count" => $row["count"]);
    }
}

/*
 * Get the list of $count users that have posted the most
 * Order by the number of posts (descending), and then by username (A-Z)
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'users' => [ (Array of user IDs) ]
 * )
 */
function get_most_active_users($dbh, $count = 10) {
    $sql = pg_prepare($dbh, "most_active_user", "SELECT username, count(*) from cmu_post GROUP BY username ORDER BY count DESC, username LIMIT $1");
    $sql = pg_execute($dbh, "most_active_user", array($count));

    if (!$sql) {
        return array("status" => 0);
    }
    else {
        $result = array();
        while ($row = pg_fetch_array($sql)) {
            array_push($result, $row["username"]);
        }
        return array("status" => 1, "users" => $result);
    }
}

/*
 * Get the list of $count posts posted after $from that have the most likes.
 * Order by the number of likes (descending)
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 *		'posts' => [ (Array of post objects) ]
 * )
 * Each post should be of the form:
 * array(
 *		'pID' => (INTEGER)
 *		'username' => (USERNAME)
 *		'title' => (TITLE OF POST)
 *    'content' => (CONTENT OF POST)
 *		'time' => (UNIXTIME INTEGER)
 * )
 */
function get_most_popular_posts($dbh, $count = 10, $from = 0) {
    $sql1 = pg_prepare($dbh, "most_popular_posts", "SELECT l.post_id FROM cmu_post p JOIN cmu_likes l ON l.post_id = p.post_id WHERE create_time > $1 GROUP BY l.post_id, p.username ORDER BY count(*) DESC, p.username LIMIT $2");
    $sql1 = pg_execute($dbh, "most_popular_posts", array($from, $count));

    if (!$sql1) {
        return array("status" => 0);
    }
    else {
        $result = array();
        while ($row = pg_fetch_array($sql1)) {
            $sql2 = pg_prepare($dbh, "get_post", "SELECT * FROM cmu_post WHERE post_id = $1");
            $sql2 = pg_execute($dbh, "get_post", array($row["post_id"]));
            
            if (!$sql2) {
                return array("status" => 0);
            }
            $post = pg_fetch_array($sql2);
            $new_post = array("pID" => $post["post_id"],
                              "username" => $post["username"],
                              "title" => $post["title"],
                              "content" => $post["content"],
                              "time" => $post["create_time"]);
            array_push($result, $new_post);
        }
        return array("status" => 1, "posts" => $result);
    }
}

/*
 * Recommend posts for user $user.
 * A post $p is a recommended post for $user if like minded users of $user also like the post,
 * where like minded users are users who like the posts $user likes.
 * Result should not include posts $user liked.
 * Rank the recommended posts by how many like minded users like the posts.
 * The set of like minded users should not include $user self.
 *
 * Return associative array of the form:
 * array(
 *    'status' =>   (1 for success and 0 for failure)
 *    'posts' => [ (Array of post objects) ]
 * )
 * Each post should be of the form:
 * array(
 *		'pID' => (INTEGER)
 *		'username' => (USERNAME)
 *		'title' => (TITLE OF POST)
 *    'content' => (CONTENT OF POST)
 *		'time' => (UNIXTIME INTEGER)
 * )
 */
function get_recommended_posts($dbh, $count = 10, $user) {
    $user_exist = pg_prepare($dbh, "user_exist", "SELECT * FROM cmu_user WHERE username = $1");
    $user_exist = pg_execute($dbh, "user_exist", array($user));

    if (!$user_exist || pg_num_rows($user_exist) == 0) {
        return array("status" => 0);
    }

    $sql1 = pg_prepare($dbh, "recommend", "SELECT post_id FROM cmu_likes WHERE username in (SELECT username FROM cmu_likes WHERE post_id IN (SELECT post_id FROM cmu_likes WHERE username = $1) AND username != $2) AND post_id NOT IN (SELECT post_id FROM cmu_likes WHERE username = $3) GROUP BY post_id ORDER BY count(*) DESC LIMIT $4");
    $sql1 = pg_execute($dbh, "recommend", array($user, $user, $user, $count));

    if (!$sql1) {
        return array("status" => 0);
    }
    else {
        $result = array();
        while ($row = pg_fetch_array($sql1)) {
            $sql2 = pg_prepare($dbh, "get_post", "SELECT * FROM cmu_post WHERE post_id = $1");
            $sql2 = pg_execute($dbh, "get_post", array($row["post_id"]));
            
            if (!$sql2) {
                return array("status" => 0);
            }
            $post = pg_fetch_array($sql2);
            $new_post = array("pID" => $post["post_id"],
                              "username" => $post["username"],
                              "title" => $post["title"],
                              "content" => $post["content"],
                              "time" => $post["create_time"]);
            array_push($result, $new_post);
        }
        return array("status" => 1, "posts" => $result);
    }
}

/*
 * Delete all tables in the database and then recreate them (without any data)
 * Return associative array of the form:
 * array(
 *		'status' =>   (1 for success and 0 for failure)
 * )
 */
function reset_database($dbh) {
    $sql1 = pg_prepare($dbh, "drop_likes", "DELETE FROM cmu_likes");
    $sql1 = pg_execute($dbh, "drop_likes", array());
    $sql2 = pg_prepare($dbh, "drop_post", "DELETE FROM cmu_post");
    $sql2 = pg_execute($dbh, "drop_post", array());
    $sql3 = pg_prepare($dbh, "drop_user", "DELETE FROM cmu_user");
    $sql3 = pg_execute($dbh, "drop_user", array());
    $sql4 = pg_prepare($dbh, "drop_seq", "ALTER SEQUENCE cmu_post_post_id_seq RESTART WITH 1");
    $sql4 = pg_execute($dbh, "drop_seq", array());

    if ($sql1 != false && $sql2 != false && $sql3 != false) {
        return array("status" => 1);
    } else {
        return array("status" => 0);
    }
}

?>
