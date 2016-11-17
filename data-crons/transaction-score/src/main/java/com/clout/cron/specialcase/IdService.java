package com.clout.cron.specialcase;

/**
 * 
 * @author Ung
 *
 */
public interface IdService {

	public Long getLastStoreId();

	public Long getLastTransactionsId();
	
	public void setLastStoreId(Long lastStoreId);

	public void setLastTransactionsId(Long lastTransactionsId);
}
