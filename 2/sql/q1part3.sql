SELECT first_name, COUNT(*) AS count FROM people GROUP BY first_name ORDER BY COUNT(*) DESC, first_name ASC LIMIT 1;