package com.clout.cron.batch.mapper;

import java.math.BigInteger;
import java.sql.ResultSet;
import java.sql.SQLException;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.jdbc.core.RowMapper;

import com.clout.cron.batch.model.TransactionsRaw;

public class TransactionsRawMapper implements RowMapper<TransactionsRaw> {

	private final static Logger logger = LoggerFactory.getLogger(TransactionsRawMapper.class);

	@Override
	public TransactionsRaw mapRow(ResultSet rs, int arg1) throws SQLException {
		TransactionsRaw trans = new TransactionsRaw();
		trans.setId(rs.getInt("id"));
		trans.setTransactionId(rs.getString("transaction_id"));
		trans.setTransactionType(rs.getString("transaction_type"));
		trans.setUserId(rs.getInt("_user_id"));
		trans.setBankId(rs.getInt("_bank_id"));
		trans.setAmount(rs.getDouble("amount"));
		trans.setLatitude(rs.getString("latitude"));
		trans.setLongitude(rs.getString("longitude"));
		trans.setZipcode(rs.getString("zipcode"));
		trans.setState(rs.getString("state"));
		trans.setCity(rs.getString("city"));
		trans.setAddress(rs.getString("address"));

		trans.setContactTelephone(rs.getString("contact_telephone"));
		trans.setWebsite(rs.getString("website"));
		trans.setConfidenceLevel(rs.getFloat("confidence_level"));
		
		
		trans.setPlaceType(rs.getString("place_type"));
		
		trans.setCurrencyType(rs.getString("currency_type"));
		trans.setInstitutionTransactionId(rs.getString("institution_transaction_id"));
		trans.setCorrectInstitutionTransactionId("correct_institution_transaction_id");
		trans.setCorrectAction(rs.getString("correct_action"));
		trans.setServerTransactionId(rs.getString("server_transaction_id"));
		trans.setCheckNumber(rs.getString("check_number"));
		trans.setReferenceNumber(rs.getString("reference_number"));
		trans.setConfirmationNumber(rs.getString("confirmation_number"));
		trans.setPayeeId(rs.getString("payee_id"));
		trans.setPayeeName(rs.getString("payee_name"));
		trans.setExtendedPayeeName(rs.getString("extended_payee_name"));
		trans.setMemo(rs.getString("memo"));
		trans.setType(rs.getString("type"));
		trans.setValueType(rs.getString("value_type"));
		trans.setCurrencyRate(rs.getString("currency_rate"));
		trans.setOriginalCurrency(rs.getString("original_currency"));
		trans.setPostedDate(rs.getDate("posted_date"));
		//trans.setUserDate(rs.getString("user_date"));
		trans.setAvailableDate(rs.getDate("available_date"));
		trans.setRunningBalanceAmount(rs.getDouble("running_balance_amount"));
		trans.setPending(rs.getString("pending"));
		trans.setNormalizedPayeeName(rs.getString("normalized_payee_name"));
		trans.setMerchant(rs.getString("merchant"));
		trans.setSic(rs.getString("sic"));
		trans.setSource(rs.getString("source"));
		trans.setContextType(rs.getString("context_type"));
		trans.setScheduleC(rs.getString("schedule_c"));
		trans.setCloutTransactionId(rs.getString("clout_transaction_id"));
		trans.setSubCategoryId(rs.getString("sub_category_id"));
		trans.setRelatedAdId(rs.getString("related_ad_id"));
		trans.setApiAccount(rs.getString("api_account"));
		trans.setBankingTransactionType(rs.getString("banking_transaction_type"));
		trans.setSubaccountFundType(rs.getString("subaccount_fund_type"));
		trans.setBanking401KSourceType(rs.getString("banking_401k_source_type"));
		trans.setPrincipalAmount(rs.getDouble("principal_amount"));
		trans.setInterestAmount(rs.getDouble("interest_amount"));
		trans.setEscrowTotalAmount(rs.getDouble("escrow_total_amount"));
		trans.setEscrowFeesAmount(rs.getDouble("escrow_fees_amount"));
		trans.setEscrowInsuranceAmount(rs.getDouble("escrow_insurance_amount"));
		trans.setEscrowPmiAmount(rs.getDouble("escrow_pmi_amount"));
		trans.setEscrowTaxAmount(rs.getDouble("escrow_tax_amount"));
		trans.setEscrowOtherAmount(rs.getDouble("escrow_other_amount"));
		trans.setLastUpdateDate(rs.getDate("last_update_date"));
		trans.setIsSaved(rs.getString("is_saved"));
		trans.setIsActive(rs.getString("is_active"));
		trans.setIsProcessed(rs.getString("is_processed"));
		
		return trans;
	}
}
