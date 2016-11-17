package com.clout.cron.batch.mapper;

import java.sql.ResultSet;
import java.sql.SQLException;

import org.springframework.jdbc.core.RowMapper;

import com.clout.cron.batch.model.DataProcessingCrons;

/**
 * 
 * @author Ung
 *
 */
public class DataProcessingCronsMapper implements RowMapper<DataProcessingCrons>{

	@Override
	public DataProcessingCrons mapRow(ResultSet arg0, int arg1) throws SQLException {
			DataProcessingCrons cron = new DataProcessingCrons();
		cron.setId(arg0.getInt("id"));
		cron.setJobName(arg0.getString("job_name"));
		cron.setLastProcessedKey(arg0.getInt("last_processed_key"));
		cron.setQuota(arg0.getInt("quota"));
		cron.setServiceEndpoint(arg0.getString("service_endpoint"));
		cron.setAlreadyExist(arg0.getInt("already_exist"));
		cron.setSuccess(arg0.getInt("success"));
		cron.setFailed(arg0.getInt("failed"));
		cron.setActive(arg0.getString("is_active"));
		cron.setLastProcessedDate(arg0.getDate("last_processed_date"));
		return cron;
	}

}
