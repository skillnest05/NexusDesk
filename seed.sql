-- ============================================================
-- Support Ticket System — Seed Data
-- Run this AFTER schema.sql
-- ============================================================

-- ---- Agents ----
INSERT INTO `agents` (`id`, `name`, `email`) VALUES
(1, 'Sarah Johnson', 'sarah.johnson@support.com'),
(2, 'Michael Chen', 'michael.chen@support.com'),
(3, 'Emily Rodriguez', 'emily.rodriguez@support.com'),
(4, 'David Kim', 'david.kim@support.com'),
(5, 'Jessica Patel', 'jessica.patel@support.com');

-- ---- Tickets ----
-- Spread across statuses, categories, priorities, sentiments, and the last ~14 days
INSERT INTO `tickets` (`id`, `title`, `description`, `category`, `priority`, `status`, `sentiment`, `ai_suggested_reply`, `customer_name`, `customer_email`, `agent_id`, `created_at`, `updated_at`, `resolved_at`) VALUES

(1, 'Cannot access my account after password reset',
 'I tried resetting my password using the forgot password link but now I cannot log in at all. I get an error saying "Invalid credentials" even though I just set a new password. This is very frustrating.',
 'Account', 'High', 'In Progress', 'Frustrated',
 'I understand how frustrating this must be. Let me look into your account right away. Could you please try clearing your browser cache and cookies, then attempt the login again? If that doesn''t work, I''ll manually reset your credentials from our end.',
 'Robert Wilson', 'robert.wilson@email.com', 1,
 DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), NULL),

(2, 'Billing discrepancy on my last invoice',
 'My invoice #INV-2024-0892 shows a charge of $149.99 but my plan is the Basic tier at $49.99/month. It seems like I was charged for the Premium plan by mistake. Please correct this.',
 'Billing', 'High', 'Open', 'Negative',
 'Thank you for bringing this billing discrepancy to our attention. I can see the overcharge on your invoice and I''ll initiate a refund for the difference of $100.00 immediately. You should see the credit within 3-5 business days.',
 'Anna Martinez', 'anna.martinez@email.com', 2,
 DATE_SUB(NOW(), INTERVAL 11 DAY), DATE_SUB(NOW(), INTERVAL 9 DAY), NULL),

(3, 'Feature request: Dark mode support',
 'I love using the platform but I''d really appreciate a dark mode option. Working late at night with the bright white interface is hard on the eyes. This would be a great quality-of-life improvement!',
 'Feature Request', 'Low', 'New', 'Positive',
 'Thank you for your wonderful suggestion! Dark mode is actually on our product roadmap. I''ll make sure to log your vote for this feature. We appreciate users like you who help us improve the platform!',
 'James Thompson', 'james.thompson@email.com', NULL,
 DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), NULL),

(4, 'API integration returning 500 errors',
 'Since yesterday our API integration has been returning intermittent 500 errors on the /v2/orders endpoint. We rely on this for our e-commerce sync. Our integration ID is INT-44821. This is blocking our business operations.',
 'Technical', 'Urgent', 'In Progress', 'Frustrated',
 'I sincerely apologize for the disruption to your business operations. I''ve escalated this to our engineering team with the highest priority. I can see the errors in our logs for integration INT-44821. Our team is actively investigating and I''ll update you every 2 hours until this is resolved.',
 'Tech Corp Solutions', 'dev@techcorpsolutions.com', 1,
 DATE_SUB(NOW(), INTERVAL 9 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY), NULL),

(5, 'How to export data to CSV?',
 'Hi, I''m trying to figure out how to export my project data into a CSV file. I looked through the settings but couldn''t find the option. Can you point me in the right direction? Thanks!',
 'Technical', 'Low', 'Resolved', 'Neutral',
 'Great question! To export your data to CSV, go to Settings > Data Management > Export. You''ll see an "Export to CSV" button there. You can choose which data fields to include before downloading. Let me know if you need any further help!',
 'Lisa Chang', 'lisa.chang@email.com', 3,
 DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),

(6, 'Subscription cancellation request',
 'I would like to cancel my subscription effective at the end of the current billing cycle. My account email is sarah.k@email.com. Please confirm the cancellation and let me know if there will be any remaining charges.',
 'Billing', 'Medium', 'Open', 'Neutral',
 'I''m sorry to see you go. I''ve processed your cancellation request and it will take effect at the end of your current billing cycle on the 15th. You won''t be charged again. Your data will be retained for 30 days in case you change your mind. Is there anything we could have done differently?',
 'Sarah Kim', 'sarah.k@email.com', 4,
 DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY), NULL),

(7, 'Mobile app crashes on Android 14',
 'The app crashes immediately after opening on my Samsung Galaxy S24 running Android 14. I''ve tried reinstalling but the issue persists. App version 3.2.1.',
 'Technical', 'High', 'On Hold', 'Negative',
 'Thank you for the detailed report. We''ve identified a compatibility issue with Android 14 on Samsung devices and our mobile team is working on a fix. I expect a patched version (3.2.2) within the next 48 hours. I''ll notify you as soon as it''s available on the Play Store.',
 'Mark Davis', 'mark.davis@email.com', 2,
 DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY), NULL),

(8, 'Thank you for the quick support!',
 'Just wanted to say thanks for resolving my issue so quickly last time. The team was very helpful and professional. Keep up the great work!',
 'Other', 'Low', 'Closed', 'Positive',
 'Thank you so much for taking the time to share this feedback! It really means a lot to our team. We''re always here to help. Don''t hesitate to reach out anytime!',
 'Patricia Moore', 'patricia.moore@email.com', 3,
 DATE_SUB(NOW(), INTERVAL 13 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY)),

(9, 'Two-factor authentication setup help',
 'I need help setting up two-factor authentication on my account. I have the Google Authenticator app installed but I don''t see the option to enable 2FA in my security settings.',
 'Account', 'Medium', 'Resolved', 'Neutral',
 'I''d be happy to help you set up 2FA! The option is under Settings > Security > Two-Factor Authentication. Click "Enable 2FA" and scan the QR code with your Google Authenticator app. If you don''t see this option, please ensure your account is on a paid plan, as 2FA is available for paid users. Let me know if you need further assistance!',
 'Tom Anderson', 'tom.anderson@email.com', 5,
 DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),

(10, 'Webhook not triggering on status change',
 'I configured a webhook to fire when order status changes to "shipped" but it''s not triggering. I''ve verified the endpoint URL is correct and accessible. Webhook ID: WH-9923.',
 'Technical', 'High', 'New', 'Negative',
 'I''ll investigate the webhook configuration for WH-9923 right away. Could you check your webhook logs in Settings > Integrations > Webhooks to see if there are any failed delivery attempts? In the meantime, I''ll review the server-side logs for your webhook endpoint.',
 'CloudSync Inc', 'support@cloudsync.io', NULL,
 DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY), NULL),

(11, 'Request to upgrade plan mid-cycle',
 'I would like to upgrade from the Starter plan to the Business plan. Can this be done mid-billing cycle? If so, will I be charged a prorated amount or the full price?',
 'Billing', 'Medium', 'Resolved', 'Positive',
 'Great news — you can absolutely upgrade mid-cycle! The charge will be prorated, so you''ll only pay the difference for the remaining days in your current billing period. I can process the upgrade for you right now if you''d like. Just confirm and I''ll take care of it!',
 'Diana Ross', 'diana.ross@email.com', 4,
 DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),

(12, 'Data import failing with large files',
 'When I try to import a CSV file larger than 10MB, the import process times out and fails. Smaller files work fine. I have about 50,000 records to import.',
 'Technical', 'Medium', 'In Progress', 'Neutral',
 'I understand the challenge with large file imports. Our current import limit is 10MB per file. For your 50,000 records, I''d recommend splitting the CSV into batches of about 5,000 records each. Alternatively, you can use our API bulk import endpoint which handles larger datasets more efficiently. Would you like me to guide you through either option?',
 'Kevin Brown', 'kevin.brown@email.com', 1,
 DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), NULL),

(13, 'Account merge request - duplicate accounts',
 'I accidentally created two accounts with different email addresses. I''d like to merge kevin.b@email.com into kevin.brown@email.com and keep all my data. Please help.',
 'Account', 'Medium', 'New', 'Neutral',
 'I can definitely help you merge your accounts. For security purposes, I''ll need to verify ownership of both accounts. Could you please confirm the last 4 digits of the payment method on each account? Once verified, I''ll merge all data from kevin.b@email.com into kevin.brown@email.com.',
 'Kevin Brown', 'kevin.brown@email.com', NULL,
 DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), NULL),

(14, 'Suggest adding keyboard shortcuts',
 'It would be great to have keyboard shortcuts for common actions like creating new items (Ctrl+N), searching (Ctrl+K), and navigating between sections. This would really speed up the workflow for power users.',
 'Feature Request', 'Low', 'Closed', 'Positive',
 'What a fantastic suggestion! Keyboard shortcuts are definitely something our UX team has been exploring. I''ve added your specific shortcut ideas to our feature request tracker. We''ll keep you updated when this rolls out. Thank you for helping us improve!',
 'Alex Turner', 'alex.turner@email.com', 5,
 DATE_SUB(NOW(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 11 DAY), DATE_SUB(NOW(), INTERVAL 11 DAY)),

(15, 'Urgent: Payment processing down',
 'NONE of our payment transactions are going through since 2 PM today. We''re losing sales every minute. This needs immediate attention! Our merchant ID is MER-55012.',
 'Billing', 'Urgent', 'Open', 'Frustrated',
 'I completely understand the urgency and I''m treating this as our top priority. I''ve immediately escalated this to our payments engineering team. We''re investigating the issue with merchant ID MER-55012 right now. I''ll provide you with updates every 30 minutes until this is fully resolved.',
 'ShopRight LLC', 'ops@shopright.com', 2,
 DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 0 DAY), NULL);

-- ---- Ticket Replies ----
INSERT INTO `ticket_replies` (`ticket_id`, `author_role`, `author_name`, `message`, `created_at`) VALUES

-- Ticket 1 replies
(1, 'agent', 'Sarah Johnson', 'Hi Robert, I can see your account had an issue during the password reset process. I''ve manually reset your credentials. Could you try logging in again with your email and the temporary password I''m sending to your registered email?', DATE_SUB(NOW(), INTERVAL 11 DAY)),
(1, 'customer', 'Robert Wilson', 'I received the temporary password and was able to log in. However, when I try to change it to my own password, it says "password too weak" even though I''m using a strong password with special characters.', DATE_SUB(NOW(), INTERVAL 10 DAY)),

-- Ticket 2 replies
(2, 'agent', 'Michael Chen', 'Hi Anna, I''ve reviewed your account and confirmed the billing error. A refund of $100.00 has been initiated. You should see it reflected in 3-5 business days. I''m also applying a 10% discount on your next invoice as an apology for the inconvenience.', DATE_SUB(NOW(), INTERVAL 9 DAY)),

-- Ticket 4 replies
(4, 'agent', 'Sarah Johnson', 'We''ve identified the root cause — a recent deployment caused a regression in the /v2/orders endpoint. Our team is rolling back the change now. ETA for full resolution: 2 hours.', DATE_SUB(NOW(), INTERVAL 8 DAY)),
(4, 'customer', 'Tech Corp Solutions', 'Thank you for the quick response. We''re monitoring on our end. Will you confirm once the rollback is complete?', DATE_SUB(NOW(), INTERVAL 8 DAY)),
(4, 'agent', 'Sarah Johnson', 'The rollback is complete and the /v2/orders endpoint is functioning normally. We''re seeing successful responses across the board. Could you verify on your end?', DATE_SUB(NOW(), INTERVAL 7 DAY)),

-- Ticket 5 replies
(5, 'agent', 'Emily Rodriguez', 'Hi Lisa! You can find the export option under Settings > Data Management > Export. There you''ll see a button to "Export to CSV". Let me know if you need help with anything else!', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(5, 'customer', 'Lisa Chang', 'Found it! Thank you so much, that was really helpful. You can close this ticket.', DATE_SUB(NOW(), INTERVAL 5 DAY)),

-- Ticket 7 replies
(7, 'agent', 'Michael Chen', 'Hi Mark, thanks for the detailed report. We''ve reproduced the crash on our test Samsung Galaxy S24 device. The issue is related to a new Android 14 API change. We''re working on a fix. I''ll update you soon.', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(7, 'customer', 'Mark Davis', 'Any update on the fix? It''s been a few days and I rely on the mobile app daily.', DATE_SUB(NOW(), INTERVAL 4 DAY)),

-- Ticket 12 replies
(12, 'agent', 'Sarah Johnson', 'Hi Kevin, I''ve increased your import limit temporarily to 25MB. Could you try the import again? If it still fails, please share the CSV file and I''ll import it server-side for you.', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- ---- Sample Attachments ----
INSERT INTO `ticket_attachments` (`ticket_id`, `filename`, `url`, `created_at`) VALUES
(2, 'invoice-INV-2024-0892.pdf', '/uploads/2/invoice-INV-2024-0892.pdf', DATE_SUB(NOW(), INTERVAL 11 DAY)),
(7, 'crash-screenshot.png', '/uploads/7/crash-screenshot.png', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(7, 'logcat-output.txt', '/uploads/7/logcat-output.txt', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(12, 'sample-import-data.csv', '/uploads/12/sample-import-data.csv', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(15, 'payment-error-screenshot.png', '/uploads/15/payment-error-screenshot.png', DATE_SUB(NOW(), INTERVAL 1 DAY));
