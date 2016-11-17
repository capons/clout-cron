package com.clout.cron.batch.jobitems;

import java.io.Serializable;
import java.util.List;
import java.util.Map;
import java.util.concurrent.ConcurrentHashMap;
import java.util.concurrent.CopyOnWriteArrayList;
import java.util.concurrent.atomic.AtomicInteger;

import org.springframework.stereotype.Component;

/**
 * 
 * @author Ung
 *
 */
@Component("jobState")
public class JobState implements Serializable {
	
	private volatile Object lock = new Object();

	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;

	private AtomicInteger success = new AtomicInteger(0);
	private AtomicInteger failed = new AtomicInteger(0);
	private AtomicInteger rejected = new AtomicInteger(0);
	private AtomicInteger matched = new AtomicInteger(0);
	private AtomicInteger newStore = new AtomicInteger(0);
	private AtomicInteger cached = new AtomicInteger(0);
	
	private volatile Integer lastProcessedKey;
	
	private volatile Long lastCachedStoreKey;

	private Map<String, String> dupByStoreIds = new ConcurrentHashMap<String, String>();
	
	private volatile String failedJobIds;
	
	public synchronized void incrementSuccess() {
		success.incrementAndGet();
	}
	
	public void incrementFailed() {
		failed.incrementAndGet();
	}
	
	public void incrementRejected() {
		rejected.incrementAndGet();
	}
	
	public void incrementMatched() {
		matched.incrementAndGet();
	}
	
	public void incrementNewStore() {
		newStore.incrementAndGet();
	}
	
	public void incrementCached() {
		cached.incrementAndGet();
	}
	
	public Long getLastCachedStoreKey() {
		synchronized(lock) {
			return lastCachedStoreKey;
		}
	}

	public void setLastCachedStoreKey(Long lastCachedStoreKey) {
		synchronized(lock) {
			if(this.lastCachedStoreKey == null || this.lastCachedStoreKey < lastCachedStoreKey) {
				this.lastCachedStoreKey = lastCachedStoreKey;
			}
		}
	}

	public void setLastProcessedKey(Integer lastKey) {
		synchronized(lock) {
			if(this.lastProcessedKey == null || this.lastProcessedKey < lastKey) {
				this.lastProcessedKey = lastKey;
			}
		}
	}

	public Integer getLastProcessedKey() {
		synchronized(lock) {
			return lastProcessedKey;
		}
	}

	public int getSuccess() {
		return success.intValue();
	}

	public int getFailed() {
		return failed.intValue();
	}

	public int getRejected() {
		return rejected.intValue();
	}

	public int getMatched() {
		return matched.intValue();
	}

	public int getNewStore() {
		return newStore.intValue();
	}

	public int getCached() {
		return cached.intValue();
	}

	public Map<String, String> getDupByStoreIds() {
		return dupByStoreIds;
	}

	public void setDupByStoreIds(Map<String, String> concurrentMap) {
		this.dupByStoreIds = concurrentMap;
	}

	public String getFailedJobIds() {
		synchronized(lock) {
			return failedJobIds;
		}
	}

	public void setFailedJobIds(String failedJobIds) {
		synchronized(lock) {
			if(this.failedJobIds == null) {
				this.failedJobIds = failedJobIds;
			}
			else {
				this.failedJobIds += failedJobIds;
			}
		}
	}
	
}
