package com.clout.test.tasklet;

import java.util.ArrayList;
import java.util.List;

import javax.annotation.Resource;

import org.junit.Test;
import org.junit.runner.RunWith;
import org.springframework.data.mongodb.core.MongoTemplate;
import org.springframework.data.mongodb.core.query.Criteria;
import org.springframework.data.mongodb.core.query.Query;
import org.springframework.test.context.ContextConfiguration;
import org.springframework.test.context.junit4.SpringJUnit4ClassRunner;

import com.clout.cron.batch.model.Store;
import com.clout.cron.batch.storekeywords.StoreKeywords;
import com.mongodb.BasicDBObject;
import com.mongodb.BulkWriteOperation;
import com.mongodb.BulkWriteResult;
import com.mongodb.DBCollection;

@RunWith(SpringJUnit4ClassRunner.class)
@ContextConfiguration({"classpath:/spring/batch/jobs/common-context.xml",  "classpath:/spring/batch/database.xml"})
public class MongoTest {
	
	@Resource
	private MongoTemplate mongoTemplate;

	@Test
	public void testStoreUpdate() {
		Query query = new Query();
		query.addCriteria(Criteria.where("store_id").is(1));

		List<Store> stores = mongoTemplate.find(query, Store.class, "bname");
		
		if(stores != null) {
			for(Store s : stores) {
				System.out.println("store city " + s.getCity());
				System.out.println("store name " + s.getName());
				System.out.println("store state " + s.getState());
				System.out.println("store zipcode " + s.getZipcode());
				System.out.println("store address " + s.getAddressLine1());
			}
		}
		
		List<StoreKeywords> keys = mongoTemplate.find(query, StoreKeywords.class, "bname");
		for(StoreKeywords s : keys) {
			System.out.println("store keywords " + s.getKeyWords());
			System.out.println("store public store keys " + s.getPublicStoreKey());
		}
		
		
		List<StoreKeywords> keywords = new ArrayList<StoreKeywords>();
		StoreKeywords key1 = new StoreKeywords();
		key1.setStoreId(1);
		key1.setKeyWords("abortion alternatives information services, acupuncture acupressure, acupuncture acupressure specialists, acupuncture physicians surgeons, addiction information treatment, aids hiv information referral services, alcohol drug abuse information treatment, allergy immunology physicians surgeons, alternative medicine, alternative medicine practitioners, analytical testing laboratories, anesthesiology physicians, animal health, animal hospitals, artificial nails eyelashes, audiologists, birth control family planning information services, blood banks, blood typing testing, cancer clinics, cancer information referral services, cardiology physicians surgeons, caregivers, cemeteries crematories, cemeteries memorial parks, chiropractic clinics, chiropractic information referral services, chiropractors, clinics, clinics medical centers, contact lenses, cosmetic reconstructive surgeons, cosmetic dentists, craniosacral therapy, cremation services, crisis centers, dental clinics, dental equipment supplies, dental hygienists, dental implants, dental laboratories, dentists, denture service centers, dentures, dermatology physicians surgeons, developmental disabilities information services, dialysis clinics, dietitians, disabilities special needs equipment supplies retail, disabled elderly home health care, drug alcohol detection testing, drug stores pharmacies, drugs medications, elder care, emergency critical care physicians surgeons, emergency ambulance services, emergency services dentists, emergency services veterinarians, endocrinology metabolism physicians surgeons, endodontics dentists, eyeglasses sunglasses goggles, eyewear, family general practice physicians surgeons, family planning, family planning birth control clinics, family practice chiropractors, foot ankle surgeons, forensic testing laboratories, funeral services, gastroenterology physicians surgeons, general anesthesia sedation dentists, general surgeons, geriatric care nursing homes, group medical practice, group practice chiropractors, gynecology obstetrics physicians surgeons, health welfare agencies, health wellness programs, health care consultants, health care management, health care management consultants, health care plans, health care professionals, health care providers, health information referral services, health maintenance organizations, hearing aids assistive devices service repair, hematology physicians surgeons, herbs retail, holistic health practitioners, home care services, home health care agencies, home health care equipment supplies, hospice services, hospital equipment supplies retail, hospitals, hypnotherapy, hypnotherapy psychiatry physicians, independent living services, infectious disease physicians surgeons, intermediate care nursing homes, internal medicine physicians surgeons, laser vision correction, licensed psychologists, marriage family counseling, maxillofacial physicians surgeons, medical dental x ray laboratories, medical surgical emergency services, medical billing services, medical diagnostic clinics, medical diagnostic services, medical equipment supplies rental leasing, medical equipment supplies retail, medical equipment service repair, medical examinations, medical imaging, medical laboratories, medical research development, medical services, medical services organizations, medical spas, medical testing, men s health physicians surgeons, mental health, mental health clinics, mental health counselors, mental health practitioners, midwives, naturopathic clinics, nephrology physicians surgeons, neurology physicians surgeons, non prescription medicines, nurse practitioners, nurses, nurses registered professional rn, nursing convalescent homes, nursing personal care facilities, nutrition consultants, nutritionists, occupational industrial health safety, occupational industrial medicine physicians surgeons, occupational therapy rehabilitation, oncology physicians surgeons, ophthalmology physicians surgeons, optical goods retail, optical goods service repair, opticians, optometrists, oral maxillofacial pathology surgery dentists, oral surgeons, orthodontics dentists, orthopedic appliances retail, orthopedic shoes, orthopedics chiropractors, orthopedics physicians surgeons, osteopathic physicians surgeons, osteoporosis physicians surgeons, otolaryngology physicians surgeons, oxygen equipment supplies, pain management physicians surgeons, paternity testing, pathology physicians surgeons, pediatrics dentists, pediatrics physicians surgeons, pedodontics dentists, periodontics dentists, pharmacists, pharmacy pharmaceutical consultants, physical therapists, physical therapy, physical therapy clinics, physicians surgeons, physicians surgeons information referral services, physicians assistants, podiatry clinics, podiatry information referral services, podiatry physicians surgeons, pre arranged funeral plans, pregnancy counseling information services, prescription services, preventive medicine veterinarians, proctology physicians surgeons, prosthetic artificial limbs, prosthetics, prosthodontics dentists, psychiatric hospitals, psychiatry physicians, psychologists, psychotherapists, pulmonary respiratory physicians surgeons, radiology physicians surgeons, rehabilitation centers, rehabilitation chiropractors, rehabilitation medicine physicians surgeons, rehabilitation services, rheumatology physicians surgeons, sexually transmitted diseases testing treatment, sleep disorders information treatment centers, small animal veterinarians, social human services, speech hearing, speech language pathologists, sports medicine chiropractors, sports medicine physical therapists, sports medicine physicians surgeons, sports medicine podiatry physicians surgeons, stress management counseling, surgery veterinarians, surgical centers, teeth whitening, testing laboratories, therapeutic massage, urology physicians surgeons, vascular medicine physicians surgeons, veterinarians, veterinary information referral services, veterinary laboratories, vitamins food supplements retail, web site design, weight control centers, weight loss control, weight loss control consultants, weight loss control programs, wheelchair lifts scooters, wheelchairs retail, yoga instruction therapy,");
		key1.setPublicStoreKey("ikondu-medical-center-desmond-ikondu-md-2502-w-trenton-rd--edinburg-tx-78539-united-states");
		keywords.add(key1);
		
		StoreKeywords key2 = new StoreKeywords();
		key2.setStoreId(2);
		key2.setKeyWords("accident health insurance, accident attorneys, accountants, accountants information referral services, accounting tax consultants, accounting auditing bookkeeping services, administrative governmental law attorneys, adoption attorneys, appeals attorneys, appraisers, arbitration mediation services, arbitration mediation services attorneys, arbitrators, atm locations, attorneys, attorneys information referral services, auto financing loans, auto insurance, auto title loans, bail bonds, bail bondsmen, banking investment law attorneys, bankruptcy attorneys, bankruptcy services, banks, bookkeeping services, business insurance, cash advance loans, certified public accountants, civil law attorneys, collection agencies, commercial savings banks, construction law attorneys, corporate business attorneys, corporate finance securities attorneys, court convention reporters, court reporting, credit debt counseling services, credit card merchant services, credit card plans services, credit reporting agencies consultants, credit unions, creditors rights attorneys, criminal law attorneys, custody support law attorneys, disability law attorneys, discrimination civil rights attorneys, divorce mediation services, divorce attorneys, drug charges attorneys, dui dwi attorneys, elder law attorneys, employment labor law attorneys, environmental natural resources attorneys, escrow services, escrow services, estate appraisal liquidation, estate planning administration, estate planning administration attorneys, family law attorneys, financial brokers, financial counselors, financial management consulting, financial planning consultants services, financial services, financing consultants, foreign currency exchange brokers, franchising, general practice attorneys, group insurance, health insurance, holding companies, homeowners renters insurance, immigration naturalization consultants, immigration law attorneys, income tax consultants, income tax services, insurance, insurance adjusters, insurance agents brokers, insurance annuities, insurance claims services, insurance consultants advisors, insurance law attorneys, intellectual property attorneys, investment advisory services, investment bankers, investment management, investment securities, investment services, investments, investors, law enforcement, legal counsel prosecution, legal forms preparation services, legal service plans, legal services, life insurance, liquidators, loan financing services, malpractice negligence attorneys, medical malpractice attorneys, mergers acquisitions, money orders transfer services, mortgage loan banks, mutual funds brokers, newspaper publishers representatives, patent trademark attorneys, pawn brokers shops, payroll payroll tax preparation services, payroll services systems, pension profit sharing plans, personal financial services, personal financing, personal injury attorneys, personal loans, process service, product liability law attorneys, property casualty insurance, property law attorneys, public accountants, real estate attorneys, real estate investment trusts, real estate loans, retirement planning consultants services, savings loan associations, savings banks, social security attorneys, stock bond brokers, stocks bonds, surety fidelity bonds, tax attorneys, tax consultants, tax return preparation, tax return preparation electronic filing, tax return preparation accountants, taxation monetary policy, traffic law attorneys, trial attorneys, trust companies services, vehicular accident attorneys, venture capital, workers compensation attorneys, wrongful death attorneys,");
		key2.setPublicStoreKey("ewing-insurance-44-clinton-st--hudson-oh-44236-united-states");
		keywords.add(key2);
		
		
		
		DBCollection collection = mongoTemplate.getCollection("bname");
		BulkWriteOperation bulk = collection.initializeOrderedBulkOperation();
		
		
		
		for(int i = 0; i < keywords.size(); i ++) {
			bulk.find(new BasicDBObject("store_id", keywords.get(i).getStoreId()))
			.update(new BasicDBObject("$set", 
					new BasicDBObject("public_store_key", keywords.get(i).getPublicStoreKey() ).append("key_words", keywords.get(i).getKeyWords() ) ));
		}
		
		BulkWriteResult writeResult = bulk.execute();
		
		

		stores = mongoTemplate.find(query, Store.class, "bname");
		
		for(Store s : stores) {
			System.out.println("store city " + s.getCity());
		}
		
		
		keys = mongoTemplate.find(query, StoreKeywords.class, "bname");
		for(StoreKeywords s : keys) {
			System.out.println("store keywords " + s.getKeyWords());
			System.out.println("store public store keys " + s.getPublicStoreKey());
		}
	}
}
