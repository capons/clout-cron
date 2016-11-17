package com.clout.test.tasklet;

import java.util.Properties;

import javax.mail.Message;
import javax.mail.Session;
import javax.mail.Transport;
import javax.mail.internet.InternetAddress;
import javax.mail.internet.MimeMessage;

import org.junit.Test;

public class SampleEmail {

	    static final String FROM = "khimu@clout.com";   // Replace with your "From" address. This address must be verified.
	    static final String TO = "khimu@clout.com";  // Replace with a "To" address. If your account is still in the 
	                                                       // sandbox, this address must be verified.
	    
	    static final String BODY = "This email was sent through the Amazon SES SMTP interface by using Java.";
	    static final String SUBJECT = "Amazon SES test (SMTP interface accessed using Java)";
	    
	    // Supply your SMTP credentials below. Note that your SMTP credentials are different from your AWS credentials.
	    static final String SMTP_USERNAME = "AKIAJMTOENKZZUM3K2GQ";  // Replace with your SMTP username.
	    static final String SMTP_PASSWORD = "AuC3QWS99vZiFtaO5p6LAUBLbwP1+ouMryrVy+CmtZtw";  // Replace with your SMTP password.
	    
	    // Amazon SES SMTP host name. This example uses the US West (Oregon) region.
	    static final String HOST = "email-smtp.us-east-1.amazonaws.com";    
	    
	    // The port you will connect to on the Amazon SES SMTP endpoint. We are choosing port 25 because we will use
	    // STARTTLS to encrypt the connection.
	    static final int PORT = 587;

	    @Test
	   public void testEmail() throws Exception {

	        // Create a Properties object to contain connection configuration information.
	    	Properties props = System.getProperties();
	    	props.put("mail.transport.protocol", "smtp");
	    	props.put("mail.smtp.port", PORT); 
	    	
	    	// Set properties indicating that we want to use STARTTLS to encrypt the connection.
	    	// The SMTP session will begin on an unencrypted connection, and then the client
	        // will issue a STARTTLS command to upgrade to an encrypted connection.
	    	props.put("mail.smtp.auth", "true");
	    	props.put("mail.smtp.starttls.enable", "true");
	    	props.put("mail.smtp.starttls.required", "true");
	    	props.put("mail.debug", "true");

	        // Create a Session object to represent a mail session with the specified properties. 
	    	Session session = Session.getDefaultInstance(props);

	        // Create a message with the specified information. 
	        MimeMessage msg = new MimeMessage(session);
	        msg.setFrom(new InternetAddress(FROM));
	        msg.setRecipient(Message.RecipientType.TO, new InternetAddress(TO));
	        msg.setSubject(SUBJECT);
	        msg.setContent(BODY,"text/plain");
	            
	        // Create a transport.        
	        Transport transport = session.getTransport();
	                    
	        // Send the message.
	        try
	        {
	            System.out.println("Attempting to send an email through the Amazon SES SMTP interface...");
	            
	            // Connect to Amazon SES using the SMTP username and password you specified above.
	            transport.connect(HOST, SMTP_USERNAME, SMTP_PASSWORD);
	        	
	            // Send the email.
	            transport.sendMessage(msg, msg.getAllRecipients());
	            System.out.println("Email sent!");
	        }
	        catch (Exception ex) {
	        	ex.printStackTrace();
	            System.out.println("The email was not sent.");
	            System.out.println("Error message: " + ex.getMessage());
	        }
	        finally
	        {
	            // Close and terminate the connection.
	            transport.close();        	
	        }
	    }
	}