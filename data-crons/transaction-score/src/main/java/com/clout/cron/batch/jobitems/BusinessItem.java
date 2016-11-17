package com.clout.cron.batch.jobitems;

import com.clout.cron.batch.model.Store;
import com.clout.cron.batch.model.TransactionSubCategories;
import com.clout.cron.batch.model.Transactions;
import com.clout.cron.batch.model.TransactionsRaw;

public class BusinessItem extends BaseItem {

	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;

	private Store store;

	private boolean updateStore;

	private Transactions transactions;

	private TransactionSubCategories categories;
	
	private TransactionsRaw raw;


	public Store getStore() {
		return store;
	}

	public void setStore(Store store) {
		this.store = store;
	}

	public Transactions getTransactions() {
		return transactions;
	}

	public void setTransactions(Transactions transactions) {
		this.transactions = transactions;
	}

	public boolean isUpdateStore() {
		return updateStore;
	}

	public void setUpdateStore(boolean updateStore) {
		this.updateStore = updateStore;
	}

	public TransactionSubCategories getCategories() {
		return categories;
	}

	public void setCategories(TransactionSubCategories categories) {
		this.categories = categories;
	}

	public TransactionsRaw getRaw() {
		return raw;
	}

	public void setRaw(TransactionsRaw raw) {
		this.raw = raw;
	}

}
