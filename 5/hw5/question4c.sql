SET work_mem = '32MB';
SET enable_mergejoin = false; 
SET enable_hashjoin = false;
CREATE INDEX sessid_index ON clicks (sessid);
