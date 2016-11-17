package com.clout.cron.batch.jobitems;

import com.clout.cron.batch.model.Transactions;

/**
 * 
 * @author Ung
 *
 */
public class ScoreItem extends BaseItem {

	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;

	private Transactions transactions;

	// spending_last180days
	private String userTransactionKeyByDays;

	// spending_last360days
	private String userTransactionKeyByMonths;

	// spending_total
	private String userTransactionKeyByLifetime;

	// my_store_spending_last90days
	private String userStoreTransactionKeyByDays;

	// my_store_spending_last12months
	private String userStoreTransactionKeyByMonths;

	// my_store_spending_lifetime
	private String userStoreTransactionKeyByLifetime;

	// my_chain_spending_last90days
	private String userChainTransactionKeyByDays;

	// my_chain_spending_last12months
	private String userChainTransactionKeyByMonths;

	// my_chain_spending_lifetime
	private String userChainTransactionKeyByLifetime;

	// ad_spending_last180days
	private String userRelatedPromoTransactionKeyByDays;

	// ad_spending_last360days
	private String userRelatedPromoTransactionKeyByMonths;

	// ad_spending_total
	private String userRelatedPromoTransactionKeyByLifetime;

	private Double amount;

	private Long transactionDateByDays;
	private Long transactionDateByMonths;

	private boolean unqualified = false;

	public Transactions getTransactions() {
		return transactions;
	}

	public void setTransactions(Transactions transactions) {
		this.transactions = transactions;
	}

	public String getUserTransactionKeyByDays() {
		return userTransactionKeyByDays;
	}

	public void setUserTransactionKeyByDays(String userTransactionKeyByDays) {
		this.userTransactionKeyByDays = userTransactionKeyByDays;
	}

	public String getUserStoreTransactionKeyByDays() {
		return userStoreTransactionKeyByDays;
	}

	public void setUserStoreTransactionKeyByDays(String userStoreTransactionKeyByDays) {
		this.userStoreTransactionKeyByDays = userStoreTransactionKeyByDays;
	}

	public String getUserStoreTransactionKeyByMonths() {
		return userStoreTransactionKeyByMonths;
	}

	public void setUserStoreTransactionKeyByMonths(String userStoreTransactionKeyByMonths) {
		this.userStoreTransactionKeyByMonths = userStoreTransactionKeyByMonths;
	}

	public String getUserChainTransactionKeyByDays() {
		return userChainTransactionKeyByDays;
	}

	public void setUserChainTransactionKeyByDays(String userChainTransactionKeyByDays) {
		this.userChainTransactionKeyByDays = userChainTransactionKeyByDays;
	}

	public String getUserChainTransactionKeyByMonths() {
		return userChainTransactionKeyByMonths;
	}

	public void setUserChainTransactionKeyByMonths(String userChainTransactionKeyByMonths) {
		this.userChainTransactionKeyByMonths = userChainTransactionKeyByMonths;
	}

	public String getUserRelatedPromoTransactionKeyByDays() {
		return userRelatedPromoTransactionKeyByDays;
	}

	public void setUserRelatedPromoTransactionKeyByDays(String userRelatedPromoTransactionKeyByDays) {
		this.userRelatedPromoTransactionKeyByDays = userRelatedPromoTransactionKeyByDays;
	}

	public Double getAmount() {
		return amount;
	}

	public void setAmount(Double amount) {
		this.amount = amount;
	}

	public Long getTransactionDateByDays() {
		return transactionDateByDays;
	}

	public void setTransactionDateByDays(Long transactionDateByDays) {
		this.transactionDateByDays = transactionDateByDays;
	}

	public Long getTransactionDateByMonths() {
		return transactionDateByMonths;
	}

	public void setTransactionDateByMonths(Long transactionDateByMonths) {
		this.transactionDateByMonths = transactionDateByMonths;
	}

	public boolean isUnqualified() {
		return unqualified;
	}

	public void setUnqualified(boolean unqualified) {
		this.unqualified = unqualified;
	}

	public String getUserTransactionKeyByMonths() {
		return userTransactionKeyByMonths;
	}

	public void setUserTransactionKeyByMonths(String userTransactionKeyByMonths) {
		this.userTransactionKeyByMonths = userTransactionKeyByMonths;
	}

	public String getUserTransactionKeyByLifetime() {
		return userTransactionKeyByLifetime;
	}

	public void setUserTransactionKeyByLifetime(String userTransactionKeyByLifetime) {
		this.userTransactionKeyByLifetime = userTransactionKeyByLifetime;
	}

	public String getUserStoreTransactionKeyByLifetime() {
		return userStoreTransactionKeyByLifetime;
	}

	public void setUserStoreTransactionKeyByLifetime(String userStoreTransactionKeyByLifetime) {
		this.userStoreTransactionKeyByLifetime = userStoreTransactionKeyByLifetime;
	}

	public String getUserChainTransactionKeyByLifetime() {
		return userChainTransactionKeyByLifetime;
	}

	public void setUserChainTransactionKeyByLifetime(String userChainTransactionKeyByLifetime) {
		this.userChainTransactionKeyByLifetime = userChainTransactionKeyByLifetime;
	}

	public String getUserRelatedPromoTransactionKeyByMonths() {
		return userRelatedPromoTransactionKeyByMonths;
	}

	public void setUserRelatedPromoTransactionKeyByMonths(String userRelatedPromoTransactionKeyByMonths) {
		this.userRelatedPromoTransactionKeyByMonths = userRelatedPromoTransactionKeyByMonths;
	}

	public String getUserRelatedPromoTransactionKeyByLifetime() {
		return userRelatedPromoTransactionKeyByLifetime;
	}

	public void setUserRelatedPromoTransactionKeyByLifetime(String userRelatedPromoTransactionKeyByLifetime) {
		this.userRelatedPromoTransactionKeyByLifetime = userRelatedPromoTransactionKeyByLifetime;
	}

}
