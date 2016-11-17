package com.clout.cron.batch.common.tasklet;

import java.sql.Date;
import java.sql.Timestamp;

import javax.annotation.Resource;

import org.apache.commons.lang.StringUtils;
import org.apache.log4j.Logger;
import org.springframework.batch.core.StepContribution;
import org.springframework.batch.core.scope.context.ChunkContext;
import org.springframework.batch.core.step.tasklet.Tasklet;
import org.springframework.batch.repeat.RepeatStatus;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Scope;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.jdbc.core.PreparedStatementCallback;
import org.springframework.stereotype.Component;

import com.clout.cron.batch.CronJobException;
import com.clout.cron.batch.jobitems.JobState;

@Component("recordStateTasklet")
@Scope("step")
public class RecordStateTasklet implements Tasklet {
	private final static Logger logger = Logger.getLogger(RecordStateTasklet.class);
	
	@Resource
	private JobState jobState;
	
	@Resource(name = "localJdbcTemplate")
	private JdbcTemplate jdbcTemplate;
	
	@Value("${cron.job.state.store}")
	private String jobStateInsertStatement;
	
	@Value("#{jobParameters['jobName']}")
	private String jobName;
	
	@Value("${cron.job.daily.quota}")
	private String quota;
	
	@Value("#{jobParameters['dataProcessingJobId']}")
	private String dataProcessingJobId;

	@Override
	public RepeatStatus execute(StepContribution arg0, ChunkContext arg1) throws Exception {
		logger.debug("execute");
		if(jobState != null) {
			logger.info("jobName " + jobName);
			logger.info("success " + jobState.getSuccess());
			logger.info("failed " + jobState.getFailed());
			logger.info("alreadyExist " + jobState.getAlreadyExist());
		}
		else {
			logger.debug("JobState is null");
		}

		
		try {
			if(StringUtils.trimToNull(dataProcessingJobId) == null) {
			    PreparedStatementCallback<Boolean> psFunction = (ps) -> {
			    	
			        ps.setString(1,jobName);  
			        ps.setInt(2, jobState.getLastProcessedKey());  
			        ps.setInt(3, QuotaFactory.getQuota(jobName));
			        ps.setInt(4, jobState.getAlreadyExist());
			        ps.setInt(5, jobState.getSuccess());
			        ps.setInt(6, jobState.getFailed());
			        ps.setString(7, "Y");
			        /*
			         * TODO make this UTC date
			         */
			        ps.setTimestamp(8, new Timestamp(System.currentTimeMillis()));
			        ps.setString(9, "");
			              
			        return (Boolean) ps.execute();    
			    };
			    
			    Boolean success = jdbcTemplate.execute(jobStateInsertStatement, psFunction);
			}else {
			    PreparedStatementCallback<Boolean> psFunction = (ps) -> {
			        ps.setInt(1,jobState.getAlreadyExist());  
			        ps.setInt(2, jobState.getSuccess());  
			        ps.setInt(3, jobState.getFailed());
			        ps.setInt(4, Integer.parseInt(dataProcessingJobId));
			        return (Boolean) ps.execute();    
			    };
			    
			    Boolean success = jdbcTemplate.execute("UPDATE data_processing_crons set already_exist = ?, success = ?, failed = ? where id = ?", psFunction);
			}
		    return RepeatStatus.FINISHED;
		}catch(Exception e) {
			logger.error("Error: " + e.getMessage());
			e.printStackTrace();
			throw new CronJobException(e.getMessage());
		}

	}
}
