package com.clout.cron.batch.common.tasklet;

import java.sql.Date;

import javax.annotation.Resource;

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
import org.springframework.transaction.annotation.Transactional;

import com.clout.cron.batch.CronJobException;
import com.clout.cron.batch.jobitems.JobState;
import com.clout.cron.batch.services.StoreKeyServiceInterface;

@Component("recordStateTasklet")
@Scope("step")
public class RecordStateTasklet implements Tasklet {
	private final static Logger logger = Logger.getLogger(RecordStateTasklet.class);
	
	@Resource
	private JobState jobState;
	
	@Resource(name = "jobsJdbcTemplate")
	private JdbcTemplate jdbcTemplate;
	
	@Value("${transactions.match.job.state.store}")
	private String jobStateInsertStatement;
	
	@Resource(name = "storeKeyService")
	private StoreKeyServiceInterface storeKeyService;
	

	@Override
	@Transactional("jobsTransactionManager")
	public RepeatStatus execute(StepContribution arg0, ChunkContext arg1) throws Exception {
		logger.debug("execute");
		if(jobState != null) {
			logger.info("success " + jobState.getSuccess());
			logger.info("failed " + jobState.getFailed());
			logger.info("rejected " + jobState.getRejected());
			logger.info("matched " + jobState.getMatched());
			logger.info("new_store " + jobState.getNewStore());
			logger.info("from_cache " + jobState.getCached());
			logger.info("last cached store key " + jobState.getLastCachedStoreKey());
			logger.info("last processed transactions raw key " + jobState.getLastProcessedKey());
		}
		
		/*
		 * cache new stores and their keys
		 */
		storeKeyService.write(jobState.getDupByStoreIds());

		try {
		    PreparedStatementCallback<Boolean> psFunction = (ps) -> {

		        ps.setInt(1, jobState.getLastProcessedKey());
		        ps.setLong(2, jobState.getLastCachedStoreKey());  
		        ps.setInt(3, jobState.getRejected());
		        ps.setInt(4, jobState.getMatched());
		        ps.setInt(5, jobState.getNewStore());
		        ps.setInt(6, jobState.getCached());
		        ps.setInt(7, jobState.getSuccess());
		        ps.setInt(8, jobState.getFailed());
		        
		        /*
		         * TODO make this UTC date
		         */
		        ps.setDate(9, new Date(System.currentTimeMillis()));
		              
		        return (Boolean) ps.execute();    
		    };
		    
		    Boolean success = jdbcTemplate.execute(jobStateInsertStatement, psFunction);
		    return RepeatStatus.FINISHED;
		}catch(Exception e) {
			logger.error("Error: " + e.getMessage());
			e.printStackTrace();
			throw new CronJobException(e.getMessage());
		}
	}
}
