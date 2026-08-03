# Google Business Profile Setup and Safety

## Access requirement

Google Business Profile API access is not automatically available to every Cloud project. Use a Google Cloud project approved for the Business Profile APIs, enable the required Account Management, Business Information, Performance and My Business APIs, and configure an OAuth Web application.

Google provides the broad `business.manage` OAuth scope. Ikon SEO compensates with an application-level safety boundary:

- reports and comparisons are read-only;
- connected workflows can only stage drafts;
- no connected REST route can approve or send a draft;
- only a logged-in WordPress administrator can approve the exact stored text;
- automatic review replies and unattended posts are disabled.

## Connect

1. Open **Ikon SEO → Business Profile**.
2. Copy the displayed redirect URI.
3. Add that exact URI to the Google OAuth Web client.
4. Save the client ID and secret.
5. Connect Google and select the intended Business Profile account.
6. Load remote locations.
7. Link each remote location to the matching active Ikon SEO location record.
8. Review the website-versus-profile comparison before staging anything.

## Reviews

The review screen reads reviews for a linked verified location. Review content is cached temporarily for display and is not copied into plugin tables or activity logs.

A reply draft must reference the exact Google review resource. Approval sends only the stored text. Editors should personalize replies, avoid disclosing private information and escalate serious complaints according to the business’s own policy.

When Google supplies a `newReviewUri`, the dashboard displays the official review-request link. Request reviews from all customers without incentives or review gating. Use Business Profile’s **Get more reviews** screen to create Google’s official QR code for the same link.

## Google Posts

Standard, event and offer drafts are supported. Links must use the current website and should be generated through the Local SEO UTM builder. Posts must not include unsupported claims, fabricated scarcity or content that violates Google policies.

## Performance

The dashboard can request supported Business Profile daily metrics, including Search/Maps impressions and available website, call, direction, conversation or booking actions. It also reads the search keywords Google reports for up to 18 complete months, including privacy-threshold rows. Availability varies by profile and date. These reports are descriptive; they do not guarantee ranking.

## Operational limits

- No geo-grid rank tracking
- No Google scraping
- No automatic citation submission
- No review gating or fabricated reviews
- No unattended replies, posts or profile changes
- No promise that a draft will be accepted or displayed by Google

Domain or core profile identity changes clear the Business Profile authorization, remove remote location links and reject pending drafts. Reconnect and relink locations after verifying the new identity.

## Local Growth integration

Version 0.17.0 can synchronize review reply state and Business Profile performance into the Local Growth System. This synchronization is read-only. Review replies and Google Posts continue to use the existing staging and exact administrator-approval process.

Google discontinued Business Profile Questions and Answers API support and related notifications in 2025. Q&A automation is therefore not included.
