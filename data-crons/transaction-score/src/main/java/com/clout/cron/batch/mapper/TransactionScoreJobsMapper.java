package com.clout.cron.batch.mapper;

import java.sql.ResultSet;
import java.sql.SQLException;

import org.springframework.jdbc.core.RowMapper;

import com.clout.cron.batch.model.TransactionScoreJobs;

/**
 * 
 * @author Ung
 *
 */
public class TransactionScoreJobsMapper implements RowMapper<TransactionScoreJobs>{

	@Override
	public TransactionScoreJobs mapRow(ResultSet arg0, int arg1) throws SQLException {
		TransactionScoreJobs cron = new TransactionScoreJobs();
		cron.setId(arg0.getInt("id"));
		cron.setLastProcessedKey(arg0.getInt("last_processed_key"));
		cron.setLastCachedStoreKey(arg0.getLong("last_cached_store_key"));
		cron.setRejected(arg0.getInt("rejected"));
		cron.setMatched(arg0.getInt("matched"));
		cron.setCached(arg0.getInt("from_cache"));
		cron.setNewStore(arg0.getInt("new_store"));
		cron.setSuccess(arg0.getInt("success"));
		cron.setFailed(arg0.getInt("failed"));
		cron.setLastProcessedDate(arg0.getDate("last_processed_date"));
		return cron;
	}

}
