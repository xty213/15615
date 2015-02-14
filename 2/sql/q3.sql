CREATE VIEW game_balance AS SELECT id, opponent, (points_scored - points_suffered) AS balance FROM games;
CREATE VIEW cum_game_balance AS SELECT b1.id, SUM(b2.balance) AS cumulativeBalance FROM game_balance b1, game_balance b2 WHERE b1.id >= b2.id GROUP BY b1.id ORDER BY b1.id;
SELECT b.id, opponent, cumulativeBalance FROM cum_game_balance b JOIN games g ON g.id = b.id;