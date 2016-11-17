package com.clout.cron.specialcase;

import java.util.concurrent.atomic.AtomicLong;

/**
 * 
 * @author Ung
 *
 */
public class IdServiceImpl implements IdService {
	
	private AtomicLong lastStoreId;

	private AtomicLong lastTransactionsId;
	
	public IdServiceImpl() {
	}

	public void setLastStoreId(Long lastStoreId) {
		this.lastStoreId = new AtomicLong(lastStoreId);
	}

	public void setLastTransactionsId(Long lastTransactionsId) {
		this.lastTransactionsId = new AtomicLong(lastTransactionsId);
	}

	public Long getLastStoreId() {
		return lastStoreId.incrementAndGet();
	}

	public Long getLastTransactionsId() {
		return lastTransactionsId.incrementAndGet();
	}

}
