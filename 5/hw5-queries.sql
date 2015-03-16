-- Queries for Homework 5

-- 1.

SELECT * FROM clicks
WHERE itemid BETWEEN 214800000 AND 214819999;

-- 2.

--   a)

SELECT * FROM clicks
WHERE itemid BETWEEN 214800000 AND 214819999;

SELECT * FROM clicks WHERE itemid > 214800000;

SELECT * FROM clicks
WHERE itemid BETWEEN 214800000 AND 214819999 AND created > '2014-04-01';

SELECT * FROM clicks
WHERE itemid BETWEEN 214800000 AND 214819999 AND created IS NULL;

SELECT * FROM clicks
WHERE itemid BETWEEN 214800000 AND 214819999 OR created > '2014-04-01';

SELECT * FROM clicks WHERE itemid = 214507226;

SELECT * FROM clicks
WHERE itemid != 214507226;

--   c)

SELECT * FROM clicks
WHERE itemid BETWEEN 214800000 AND 214819999
AND created > '2014-04-01';

SELECT * FROM clicks
WHERE itemid BETWEEN 214800000 AND 214819999
AND created BETWEEN '2014-04-01' AND '2014-04-02';

SELECT * FROM clicks
WHERE itemid BETWEEN 214800000 AND 214819999
OR created BETWEEN '2014-04-01' AND '2014-04-02';

SELECT * FROM clicks
WHERE itemid < 214819999
OR created BETWEEN '2014-04-01' AND '2014-04-02';

SELECT * FROM clicks
WHERE itemid < 214819999
AND created BETWEEN '2014-04-01' AND '2014-04-02';

--    d)

SELECT * FROM clicks WHERE created BETWEEN '2014-04-01' AND '2014-04-02';

--    e)

SELECT * FROM clicks WHERE created BETWEEN '2014-04-01' AND '2015-12-31';

--    f)

SELECT * FROM clicks WHERE itemid BETWEEN 214800000 AND 214819999 ORDER BY itemid;

--    g)

SET work_mem = '25MB';

--    h)

RESET work_mem;

-- 3

SELECT sessions.*, purchases.*
FROM sessions , purchases
WHERE sessions.sessid = purchases.sessid;

--    d)

SET enable mergejoin = false;

--    e)

SET enable hashjoin = false;

--    f)

SET enable indexscan = false; SET enable bitmapscan = false;

--    g)

RESET enable_mergejoin;
RESET enable_hashjoin;
RESET enable_indexscan;
RESET enable_bitmapscan;

-- 4

SELECT c.itemid, s.browser, s.agegroup,
MAX(c.created) - MIN(c.created) as date_span
FROM sessions s, clicks c 
WHERE s.sessid = c.sessid
AND (s.zipcode BETWEEN 15000 AND 16000 
  OR s.agegroup = (SELECT MAX(agegroup) FROM sessions))
GROUP BY c.itemid, s.browser, s.agegroup 
ORDER BY c.itemid, s.browser, s.agegroup;
