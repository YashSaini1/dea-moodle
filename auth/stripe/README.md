Moodle Auth Stripe Plugin

Connect Moodle with Stripe to collect payments through cards.
Requires a Stripe account.

This plugin clones of the Email-based self-registration plugin that also enrols a user into selected courses based on a payment supplied.
When a user clicks on a link received in an email, the user is automatically enrolled in the course(s) according to selected tier.

Setup

    Install the plugin
    Register for Stripe
    Grab your Stripe API and Secret keys
    Configure the Stripe payment gateway in the plugin with those keys
    Configure Course(s) for tiers  in the plugin.
    Configure price to tiers in the plugin.
    Add 'Manual Enrolment' to the Moodle courses that you want (it set in Moodle by default)

Details

    In this implementation, it is proposed to conduct student training according to the principle of tiers.
    The site administrator in the plugin settings must mark which courses belong to a certain tier, on an accumulative basis.
    The student can choose Tier 1 which consists of courses (Course 1 and Course 2). The student can also choose Tier 2, which consists of courses (Course 1 and Course 2, and Course 3 and Course 4).
    If a student has chosen Tier 1, then in order to move to Tier 2, he will have to pay the difference in the cost of the courses.
    
    The cost of the courses is set by the site administrator in the plugin admin panel.
    
    It is possible to enroll a user on the site without payment, but without access to courses - this is Free Membership.

Additional

    It is also recommended to install an additional block auth_stripe_info for students, which will allow updating the curriculum and making payments. This block must be set by the administrator on the profile page for all users.