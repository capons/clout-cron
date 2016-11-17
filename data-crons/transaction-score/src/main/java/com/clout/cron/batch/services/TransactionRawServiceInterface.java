package com.clout.cron.batch.services;

import com.clout.cron.batch.CronJobException;

public interface TransactionRawServiceInterface {
	
	public void updateIsSaved(String ids) throws CronJobException;

}
