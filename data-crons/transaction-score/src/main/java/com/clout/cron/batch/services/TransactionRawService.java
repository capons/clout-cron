package com.clout.cron.batch.services;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Isolation;
import org.springframework.transaction.annotation.Propagation;
import org.springframework.transaction.annotation.Transactional;

import com.clout.cron.batch.CronJobException;

@Service("transactionRawService")
public class TransactionRawService implements TransactionRawServiceInterface {
	private final static Logger logger = Logger.getLogger(TransactionRawService.class);

	@Resource(name = "writeTransactionJdbcTemplate")
	private JdbcTemplate writeTransactionJdbcTemplate;

	@Override
	@Transactional(value = "writeTransactionDataSource", propagation = Propagation.REQUIRED, isolation =Isolation.SERIALIZABLE )
	public void updateIsSaved(String ids) throws CronJobException {
		logger.info("Updating transactions_raw records for range " + ids);
		
		try {
			writeTransactionJdbcTemplate.update("UPDATE transactions_raw set is_saved = 'Y' where id in (?)", new Object[] {ids});
		}catch(Exception e) {
			throw new CronJobException("Unable to update transactions_raw [" + ids + "]");
		}
	}
	
}
