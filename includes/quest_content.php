<?php
/**
 * Content bank for the 8-level Quest challenge (user/quests.php).
 *
 * Each quest level builds on the matching Learn level (see
 * includes/schema_setup.php's $levelTitles / user/level1.php...level8.php)
 * but goes deeper, then tests understanding with:
 *   - 10 quick objectives (multiple choice, 8 points each = 80 points)
 *   - 1 open-ended scenario question, graded against a set of likely
 *     acceptable answer points (up to 20 points)
 * A combined score of 80%+ passes the quest level and unlocks the next one.
 */

function get_quest_levels(): array
{
    return [

        1 => [
            'title' => 'Quest I: Internet Foundations — Deep Dive',
            'topic' => 'Internet Foundations',
            'icon' => '🌐',
            'reading' => '
                <h3>Beyond the Basics: How Data Actually Travels</h3>
                <p>You already know the Internet is a network of networks. But how does a single email or photo actually get from your phone to a friend across the world? The answer is <strong>packet switching</strong>. Instead of sending your data as one giant block, your device breaks it into small pieces called packets. Each packet is labelled with its destination address and sent independently, often travelling completely different physical routes - through fibre-optic cables under the ocean, satellite links, and local wireless networks - before being reassembled in the correct order at the other end. This design means the Internet has no single point of failure: if one path is congested or broken, packets are automatically rerouted.</p>
                <p>Underneath this sits the <strong>TCP/IP protocol suite</strong>. IP (Internet Protocol) handles addressing and routing - making sure each packet knows where it is going. TCP (Transmission Control Protocol) handles reliability - checking that every packet arrives, arrives in order, and requesting a resend if any go missing. Together they form the invisible handshake behind nearly everything you do online.</p>
                <h3>Governance in Practice: Who Actually Keeps It Running?</h3>
                <p>Internet governance is not one government or company - it is a <strong>multistakeholder ecosystem</strong>. Technical standards bodies like the IETF (Internet Engineering Task Force) write the rules that let different networks interoperate. ICANN coordinates the domain name system so that "example.com" always points to the same place worldwide. National governments regulate cybersecurity and data protection within their borders. Civil society groups advocate for digital rights and inclusion. No single actor controls the whole system - that shared responsibility is precisely what keeps the Internet open and resilient, but it also means governance decisions can be slow and require broad consensus.</p>
                <h3>Net Neutrality and the Digital Divide</h3>
                <p><strong>Net neutrality</strong> is the principle that Internet Service Providers should treat all data equally, without unfairly speeding up, slowing down, or blocking content based on its source. Debates about net neutrality shape how open and fair the Internet stays for smaller businesses and voices competing against large platforms.</p>
                <p>Meanwhile, roughly a third of the world\'s population still lacks reliable Internet access - a gap known as the <strong>digital divide</strong>. It is caused by infrastructure costs, affordability of devices and data, language barriers, and digital literacy gaps. Closing it is now considered essential to economic participation, education, and civic life in the 21st century.</p>
            ',
            'objectives' => [
                ['q' => 'What technique breaks data into small pieces before sending it across the Internet?', 'options' => ['a' => 'Data compression', 'b' => 'Packet switching', 'c' => 'Firewall filtering', 'd' => 'Cloud syncing'], 'correct' => 'b'],
                ['q' => 'Which protocol is mainly responsible for making sure packets arrive reliably and in order?', 'options' => ['a' => 'HTTP', 'b' => 'DNS', 'c' => 'TCP', 'd' => 'FTP'], 'correct' => 'c'],
                ['q' => 'What does IP primarily handle in the TCP/IP suite?', 'options' => ['a' => 'Addressing and routing packets', 'b' => 'Encrypting passwords', 'c' => 'Compressing video files', 'd' => 'Rendering web pages'], 'correct' => 'a'],
                ['q' => 'Which organisation coordinates the global domain name system?', 'options' => ['a' => 'IETF', 'b' => 'ICANN', 'c' => 'W3C', 'd' => 'UNESCO'], 'correct' => 'b'],
                ['q' => 'The "multistakeholder model" of Internet governance means:', 'options' => ['a' => 'One government controls the Internet', 'b' => 'Only private companies make the rules', 'c' => 'Governments, industry, technical bodies, and civil society share responsibility', 'd' => 'No one is responsible for the Internet'], 'correct' => 'c'],
                ['q' => 'What is net neutrality mainly concerned with?', 'options' => ['a' => 'ISPs treating all Internet traffic equally', 'b' => 'Making Wi-Fi passwords stronger', 'c' => 'Speeding up government websites only', 'd' => 'Banning social media'], 'correct' => 'a'],
                ['q' => 'The "digital divide" refers to:', 'options' => ['a' => 'A disagreement between two ISPs', 'b' => 'The gap between those with and without reliable Internet access', 'c' => 'A type of computer virus', 'd' => 'The difference between Wi-Fi and mobile data'], 'correct' => 'b'],
                ['q' => 'Why does the Internet keep working even if one physical cable is cut?', 'options' => ['a' => 'Because packets can be automatically rerouted through other paths', 'b' => 'Because every device has a backup cable', 'c' => 'Because the Internet shuts down safely instead', 'd' => 'Because only one path ever exists'], 'correct' => 'a'],
                ['q' => 'Which of these is NOT a typical cause of the digital divide?', 'options' => ['a' => 'High cost of devices and data', 'b' => 'Lack of infrastructure in remote areas', 'c' => 'Excess of too many domain names', 'd' => 'Low digital literacy'], 'correct' => 'c'],
                ['q' => 'Which best describes the IETF\'s role?', 'options' => ['a' => 'It writes technical standards that let different networks interoperate', 'b' => 'It sells domain names to the public', 'c' => 'It polices online content in every country', 'd' => 'It manufactures networking hardware'], 'correct' => 'a'],
            ],
            'scenario' => [
                'prompt' => 'Your rural community is about to get reliable Internet access for the very first time next month. As the volunteer coordinating a digital-literacy welcome programme, describe in a few sentences what you would prioritise so the community benefits fully and safely from this new access.',
                'points' => [
                    ['digital literacy', 'training', 'teach', 'workshop', 'educate'],
                    ['safe', 'safety', 'passwords', 'scams', 'phishing', 'security'],
                    ['equal access', 'everyone', 'inclusive', 'all ages', 'affordable devices'],
                    ['misinformation', 'fake news', 'verify', 'fact-check', 'critical thinking'],
                    ['responsible use', 'privacy', 'appropriate use', 'guidance'],
                ],
            ],
        ],

        2 => [
            'title' => 'Quest II: Digital Citizenship — Deep Dive',
            'topic' => 'Digital Citizenship',
            'icon' => '🧑‍💻',
            'reading' => '
                <h3>What Good Digital Citizenship Actually Looks Like</h3>
                <p>Digital citizenship is more than "being nice online." It is the set of skills and habits that let someone participate in digital life confidently, safely, critically, and respectfully. A strong digital citizen understands that their online actions have real offline consequences - for themselves, and for the people on the other side of the screen.</p>
                <p>This includes managing your <strong>digital footprint</strong> (everything you post, like, and search leaves a trace), practising <strong>netiquette</strong> (the informal rules of polite online communication - things like reading before reacting, avoiding all-caps "shouting", and respecting different time zones and cultures in group chats), and knowing how to disagree productively instead of escalating into conflict.</p>
                <h3>Rights Come With Responsibilities</h3>
                <p>As a digital citizen you have rights: to access information, to express yourself, and to a reasonable expectation of privacy. But rights are balanced by responsibilities: to verify information before sharing it, to respect others\' privacy and intellectual property, and to report - rather than ignore - harmful content such as harassment or exploitation when you encounter it.</p>
                <h3>Recognising and Responding to Harm</h3>
                <p>Harmful content online ranges from mild rudeness to serious abuse: harassment, hate speech, cyberbullying, doxxing (publishing someone\'s private information without consent), and grooming. Good digital citizens learn to recognise early warning signs, avoid engaging with or amplifying harmful content, document evidence when necessary, and use reporting tools rather than retaliating - because retaliation often escalates harm rather than resolving it.</p>
                <h3>Building a Positive Reputation</h3>
                <p>Because employers, schools, and even future friends often search your name online, treating your public profile as a long-term asset - not a place to vent impulsively - protects opportunities down the line. Positive digital citizenship also means actively contributing: sharing accurate information, encouraging others, and modelling the respectful behaviour you want to see.</p>
            ',
            'objectives' => [
                ['q' => 'What is a "digital footprint"?', 'options' => ['a' => 'A type of malware', 'b' => 'The trail of data left behind by your online activity', 'c' => 'A password recovery tool', 'd' => 'An offline backup file'], 'correct' => 'b'],
                ['q' => '"Netiquette" refers to:', 'options' => ['a' => 'Internet speed measurement', 'b' => 'Informal rules for polite online communication', 'c' => 'A type of firewall', 'd' => 'A social media algorithm'], 'correct' => 'b'],
                ['q' => 'Doxxing means:', 'options' => ['a' => 'Publishing someone\'s private information without their consent', 'b' => 'Creating a strong password', 'c' => 'Blocking a user on social media', 'd' => 'Backing up files to the cloud'], 'correct' => 'a'],
                ['q' => 'If you witness cyberbullying, the best digital citizenship response is to:', 'options' => ['a' => 'Join in to fit in', 'b' => 'Ignore it completely', 'c' => 'Report it and avoid amplifying it', 'd' => 'Share it publicly for attention'], 'correct' => 'c'],
                ['q' => 'Which is a responsibility that balances your right to free expression online?', 'options' => ['a' => 'Verifying information before sharing it', 'b' => 'Posting as often as possible', 'c' => 'Using the loudest possible tone', 'd' => 'Avoiding all online discussion'], 'correct' => 'a'],
                ['q' => 'Why does a positive digital reputation matter long-term?', 'options' => ['a' => 'It has no real impact', 'b' => 'Employers, schools, and others often search your name online', 'c' => 'It only matters for celebrities', 'd' => 'It disappears automatically after a year'], 'correct' => 'b'],
                ['q' => 'What is the safest way to disagree with someone online?', 'options' => ['a' => 'Respond calmly, respectfully, and stay on topic', 'b' => 'Use insults to make your point stronger', 'c' => 'Tag as many people as possible', 'd' => 'Screenshot and mock them publicly'], 'correct' => 'a'],
                ['q' => 'Which of these is an example of exercising digital rights responsibly?', 'options' => ['a' => 'Sharing unverified claims quickly', 'b' => 'Fact-checking before you share information', 'c' => 'Copying someone else\'s work without credit', 'd' => 'Ignoring privacy settings entirely'], 'correct' => 'b'],
                ['q' => 'What should you do before posting a photo of a friend?', 'options' => ['a' => 'Post it immediately for likes', 'b' => 'Ask their permission first', 'c' => 'Add their home address as a caption', 'd' => 'Nothing - it is always fine'], 'correct' => 'b'],
                ['q' => 'Which best describes a digital citizen\'s role in fighting harmful content?', 'options' => ['a' => 'Actively recognising, reporting, and not amplifying it', 'b' => 'Sharing it widely so others can see', 'c' => 'Waiting for someone else to deal with it', 'd' => 'Arguing publicly with the person who posted it'], 'correct' => 'a'],
            ],
            'scenario' => [
                'prompt' => 'A classmate in your group chat shares an embarrassing photo of another student without asking permission, and others start commenting negatively. As a responsible digital citizen, what would you do in that moment and afterward?',
                'points' => [
                    ['ask them to remove', 'report', 'flag', 'tell an adult', 'report to platform'],
                    ['do not share further', 'do not forward', 'stop it from spreading', 'not comment negatively'],
                    ['support', 'check on', 'reach out to the student', 'empathy', 'kindness'],
                    ['consent', 'permission', 'privacy'],
                    ['speak up', 'address the group', 'explain why it is harmful'],
                ],
            ],
        ],

        3 => [
            'title' => 'Quest III: Online Privacy & Data Protection — Deep Dive',
            'topic' => 'Online Privacy & Data Protection',
            'icon' => '🔒',
            'reading' => '
                <h3>The Data Economy: Why Your Information Is Valuable</h3>
                <p>Every time you browse, search, or use an app, you generate data: what you clicked, how long you stayed, your approximate location, your device type. This is called <strong>behavioural data</strong>, and it is the foundation of the modern advertising economy - companies use it to build detailed profiles that predict what you might buy, believe, or click next. Understanding this helps explain why so many "free" services exist: if you are not paying with money, you are often paying with data.</p>
                <h3>Types of Personal Data</h3>
                <p>Personal data isn\'t just your name and email. It includes <strong>sensitive data</strong> (health information, religious beliefs, sexual orientation, biometric data like fingerprints or facial scans), <strong>behavioural data</strong> (browsing history, app usage), and <strong>metadata</strong> (data about data - like the time and location a photo was taken, even if the photo itself reveals nothing). Metadata is often underestimated: a single geotagged photo can reveal a person\'s home address or daily routine.</p>
                <h3>Core Data Protection Principles</h3>
                <p>Modern data protection frameworks (such as the GDPR) are built on a few key principles: <strong>consent</strong> (data should only be collected with clear permission), <strong>purpose limitation</strong> (data collected for one reason shouldn\'t silently be reused for another), <strong>data minimisation</strong> (only collect what is actually needed), and the <strong>right to be forgotten</strong> (individuals can request their data be deleted). Knowing these principles helps you evaluate whether an app or website is treating your data responsibly.</p>
                <h3>Practical Privacy Protection</h3>
                <p>Protecting your privacy in practice means regularly reviewing app permissions (does that flashlight app really need your contacts?), using privacy-focused browser settings, being cautious about what you share publicly, using strong unique passwords with a password manager, and reading (or at least skimming) privacy policies for red flags like broad data-sharing with "third parties."</p>
            ',
            'objectives' => [
                ['q' => 'What is "behavioural data"?', 'options' => ['a' => 'Your bank PIN', 'b' => 'Data generated by your actions, like clicks and browsing habits', 'c' => 'A type of antivirus software', 'd' => 'A government ID number'], 'correct' => 'b'],
                ['q' => 'Why do many "free" apps still make significant profits?', 'options' => ['a' => 'They charge hidden currency fees', 'b' => 'They monetise user data and attention', 'c' => 'They are funded entirely by governments', 'd' => 'They never actually make money'], 'correct' => 'b'],
                ['q' => 'Which is an example of "sensitive personal data"?', 'options' => ['a' => 'Favourite colour', 'b' => 'Health information', 'c' => 'Preferred font size', 'd' => 'Screen brightness setting'], 'correct' => 'b'],
                ['q' => 'Metadata on a photo can reveal:', 'options' => ['a' => 'Nothing at all', 'b' => 'The time and location it was taken', 'c' => 'Only the file size', 'd' => 'The photographer\'s bank details'], 'correct' => 'b'],
                ['q' => 'The data protection principle of "purpose limitation" means:', 'options' => ['a' => 'Data collected for one purpose should not silently be reused for another', 'b' => 'Companies can use data however they like', 'c' => 'Data must be deleted after one day', 'd' => 'Only governments can collect data'], 'correct' => 'a'],
                ['q' => 'The "right to be forgotten" allows individuals to:', 'options' => ['a' => 'Demand their personal data be deleted', 'b' => 'Erase other people\'s memories', 'c' => 'Delete a company\'s entire database', 'd' => 'Avoid paying taxes'], 'correct' => 'a'],
                ['q' => 'What should you check before granting an app a permission (e.g. contacts, location)?', 'options' => ['a' => 'Whether the app actually needs it to function', 'b' => 'Nothing, always allow everything', 'c' => 'Only the app\'s star rating', 'd' => 'The app icon design'], 'correct' => 'a'],
                ['q' => '"Data minimisation" means a company should:', 'options' => ['a' => 'Collect as much data as technically possible', 'b' => 'Only collect the data it actually needs', 'c' => 'Sell data to any buyer', 'd' => 'Store data forever without limits'], 'correct' => 'b'],
                ['q' => 'A red flag in a privacy policy would be:', 'options' => ['a' => 'Clear limits on data use', 'b' => 'Broad, vague sharing of data with unnamed "third parties"', 'c' => 'A short, plain-language summary', 'd' => 'An option to delete your account'], 'correct' => 'b'],
                ['q' => 'Which practice best protects your everyday privacy?', 'options' => ['a' => 'Reusing the same simple password everywhere', 'b' => 'Using strong, unique passwords with a password manager', 'c' => 'Sharing your location publicly at all times', 'd' => 'Accepting every permission request without reading it'], 'correct' => 'b'],
            ],
            'scenario' => [
                'prompt' => 'A new social app asks for access to your contacts, precise location, microphone, and camera before you\'ve even used it. Explain how you would decide whether to grant these permissions and what steps you would take to protect your privacy while still using the app.',
                'points' => [
                    ['only grant what is needed', 'necessary permissions', 'deny unnecessary'],
                    ['read the privacy policy', 'check terms', 'research the app'],
                    ['review permissions later', 'change settings', 'revoke access'],
                    ['limit', 'location off', 'turn off when not needed'],
                    ['reputable', 'trusted source', 'reviews', 'developer reputation'],
                ],
            ],
        ],

        4 => [
            'title' => 'Quest IV: Cybersecurity Basics — Deep Dive',
            'topic' => 'Cybersecurity Basics',
            'icon' => '🛡️',
            'reading' => '
                <h3>The Anatomy of a Cyber Attack</h3>
                <p>Most everyday cyber threats follow a predictable pattern: a criminal finds a way to trick you (social engineering) or exploit a technical weakness (a vulnerability), gains access to something valuable (your account, your device, your data), and then monetises it - through fraud, ransom, or selling stolen information. Understanding this pattern helps you spot threats even when the specific trick is new.</p>
                <h3>Phishing, Smishing, and Social Engineering</h3>
                <p><strong>Phishing</strong> emails impersonate trusted organisations to trick you into clicking malicious links or revealing credentials. <strong>Smishing</strong> is the same trick via SMS text messages, and <strong>vishing</strong> is the voice-call version. All rely on <strong>social engineering</strong> - manipulating human psychology (urgency, fear, curiosity, authority) rather than breaking technical systems. A message that pressures you to "act now or lose access" is a classic red flag.</p>
                <h3>Strong Passwords and Two-Factor Authentication</h3>
                <p>A strong password is long (12+ characters), unique to each account, and unpredictable - avoid names, birthdays, or common words. Since remembering dozens of unique passwords is impractical, password managers generate and store them securely. Even a strong password can be stolen, though, which is why <strong>Two-Factor Authentication (2FA)</strong> matters: it requires a second proof of identity (a code from your phone, a fingerprint) so a stolen password alone isn\'t enough to break in.</p>
                <h3>Safe Browsing and Incident Response</h3>
                <p>Safe browsing habits include checking for "https" and a padlock icon, avoiding downloads from unofficial sources, keeping software updated (updates often patch security vulnerabilities), and being sceptical of urgent pop-ups. If something does go wrong - a suspicious login, a locked-out account, a strange charge - acting fast matters: change your password immediately, enable 2FA, alert your bank or provider if financial data was involved, and report the incident rather than staying silent, since early reporting often limits the damage.</p>
            ',
            'objectives' => [
                ['q' => 'What is "social engineering" in a cybersecurity context?', 'options' => ['a' => 'Building physical computer networks', 'b' => 'Manipulating people psychologically to gain access or information', 'c' => 'A type of antivirus software', 'd' => 'Designing user-friendly websites'], 'correct' => 'b'],
                ['q' => '"Smishing" is phishing carried out via:', 'options' => ['a' => 'Phone calls', 'b' => 'SMS text messages', 'c' => 'Physical mail', 'd' => 'USB drives'], 'correct' => 'b'],
                ['q' => 'A message urging you to "act now or lose access immediately" is most likely:', 'options' => ['a' => 'A legitimate customer service message', 'b' => 'A social engineering / phishing red flag', 'c' => 'A routine software update', 'd' => 'A harmless newsletter'], 'correct' => 'b'],
                ['q' => 'What makes a password strong?', 'options' => ['a' => 'Short and easy to remember, like a birthday', 'b' => 'Long, unique, and unpredictable', 'c' => 'The same password reused everywhere', 'd' => 'Your pet\'s name'], 'correct' => 'b'],
                ['q' => 'What does Two-Factor Authentication (2FA) add to your login?', 'options' => ['a' => 'A second proof of identity beyond just the password', 'b' => 'A faster login process with no security benefit', 'c' => 'An automatic password reset every day', 'd' => 'A second, weaker password'], 'correct' => 'a'],
                ['q' => 'Why should you avoid downloading software from unofficial sources?', 'options' => ['a' => 'It is always slower', 'b' => 'It may be bundled with malware', 'c' => 'It costs more money', 'd' => 'It uses more battery'], 'correct' => 'b'],
                ['q' => 'A padlock icon and "https" in a website address generally indicate:', 'options' => ['a' => 'The connection to the site is encrypted', 'b' => 'The site is guaranteed to be trustworthy content-wise', 'c' => 'The site is government-owned', 'd' => 'The site has no ads'], 'correct' => 'a'],
                ['q' => 'Why are software updates important for security?', 'options' => ['a' => 'They often patch known vulnerabilities', 'b' => 'They always add new games', 'c' => 'They slow down your device intentionally', 'd' => 'They have no security purpose'], 'correct' => 'a'],
                ['q' => 'If you suspect your account has been compromised, the first step should be to:', 'options' => ['a' => 'Ignore it and hope it resolves itself', 'b' => 'Change your password immediately and enable 2FA', 'c' => 'Share your password with a friend to check', 'd' => 'Wait a week before doing anything'], 'correct' => 'b'],
                ['q' => 'A password manager is useful because it:', 'options' => ['a' => 'Remembers and generates strong unique passwords for you', 'b' => 'Deletes all your passwords automatically', 'c' => 'Shares your passwords with advertisers', 'd' => 'Is only useful for businesses'], 'correct' => 'a'],
            ],
            'scenario' => [
                'prompt' => 'You receive an urgent email that looks like it\'s from your bank, saying your account will be suspended unless you click a link and confirm your password within 30 minutes. Describe what you would do step-by-step.',
                'points' => [
                    ['do not click', 'avoid the link', 'not click the link'],
                    ['verify', 'contact the bank directly', 'call the official number', 'check officially'],
                    ['report', 'flag as phishing', 'report to bank', 'report the email'],
                    ['do not enter password', 'not give credentials', 'not share password'],
                    ['delete', 'ignore', 'urgency is a red flag', 'suspicious'],
                ],
            ],
        ],

        5 => [
            'title' => 'Quest V: Social Media Safety & Digital Footprint — Deep Dive',
            'topic' => 'Social Media Safety & Digital Footprint',
            'icon' => '📱',
            'reading' => '
                <h3>Your Digital Footprint Is a Puzzle Other People Can Solve</h3>
                <p>Individually, a single post rarely reveals much. But social media platforms and even strangers can piece together many small posts - a school logo in the background, a check-in location, a recognisable landmark, a class schedule mentioned in passing - into a surprisingly complete picture of where you live, work, or study, and your daily routine. This is why oversharing risk isn\'t just about one "big" mistake; it\'s cumulative.</p>
                <h3>Algorithms, Engagement, and Your Attention</h3>
                <p>Social platforms are designed to maximise engagement (time spent, interactions) because that drives advertising revenue. Recommendation algorithms learn what keeps you scrolling and increasingly show you more of it - which can create <strong>filter bubbles</strong>, where you mostly see content that confirms what you already believe, and can also amplify emotionally charged or extreme content because it tends to generate more engagement.</p>
                <h3>Cyberbullying and Online Harassment</h3>
                <p>Cyberbullying differs from in-person bullying in important ways: it can happen 24/7, reach a huge audience instantly, be anonymous, and leave a permanent digital record. Warning signs in yourself or others include withdrawal from devices or social situations, anxiety around notifications, and reluctance to talk about online activity. Good practice includes documenting evidence (screenshots with dates), blocking and reporting rather than engaging, and involving a trusted adult or platform support - silence tends to let harassment continue.</p>
                <h3>Managing Your Reputation and Healthy Habits</h3>
                <p>Reputation management means periodically searching your own name, adjusting privacy settings so only intended audiences see personal posts, thinking before posting ("would I be comfortable if a future employer saw this?"), and untagging or requesting removal of content that no longer represents you. Healthy social media habits also include scheduled breaks, muting accounts that harm your mood, and prioritising real-world relationships over performative online validation.</p>
            ',
            'objectives' => [
                ['q' => 'Why is "oversharing risk" considered cumulative rather than one big mistake?', 'options' => ['a' => 'Because platforms delete old posts automatically', 'b' => 'Because small details across many posts can be pieced together', 'c' => 'Because it only applies to celebrities', 'd' => 'Because it never actually matters'], 'correct' => 'b'],
                ['q' => 'Why are social media algorithms designed to maximise engagement?', 'options' => ['a' => 'To reduce advertising revenue', 'b' => 'Because more time spent on the platform drives ad revenue', 'c' => 'To protect user privacy', 'd' => 'Because it is legally required'], 'correct' => 'b'],
                ['q' => 'A "filter bubble" is:', 'options' => ['a' => 'A privacy setting that blocks strangers', 'b' => 'A situation where you mostly see content confirming what you already believe', 'c' => 'A type of antivirus filter', 'd' => 'A photo editing tool'], 'correct' => 'b'],
                ['q' => 'How does cyberbullying differ from traditional in-person bullying?', 'options' => ['a' => 'It cannot leave any lasting record', 'b' => 'It can happen 24/7, reach large audiences, and be anonymous', 'c' => 'It is always less harmful', 'd' => 'It only happens at school'], 'correct' => 'b'],
                ['q' => 'What is the recommended response if you witness or experience cyberbullying?', 'options' => ['a' => 'Engage and argue back publicly', 'b' => 'Document evidence, block, report, and involve a trusted adult', 'c' => 'Ignore it and delete your account', 'd' => 'Share it further to expose the bully'], 'correct' => 'b'],
                ['q' => 'A good habit for managing your online reputation is to:', 'options' => ['a' => 'Never check what is publicly visible about you', 'b' => 'Periodically search your own name and review privacy settings', 'c' => 'Post everything without thinking', 'd' => 'Assume nothing is ever seen by others'], 'correct' => 'b'],
                ['q' => 'A useful question to ask before posting something is:', 'options' => ['a' => '"Will this get the most likes possible?"', 'b' => '"Would I be comfortable if a future employer saw this?"', 'c' => '"Can I delete it in five minutes?"', 'd' => 'None - posting requires no thought'], 'correct' => 'b'],
                ['q' => 'Why can seemingly small details (like a school logo in a photo) be risky to share?', 'options' => ['a' => 'They can help identify your location or routine when combined with other posts', 'b' => 'They always get automatically removed', 'c' => 'They have no bearing on privacy', 'd' => 'They only matter in printed photos'], 'correct' => 'a'],
                ['q' => 'What is one healthy habit for social media use?', 'options' => ['a' => 'Scheduled breaks and muting accounts that harm your mood', 'b' => 'Checking notifications every minute', 'c' => 'Comparing your life to others constantly', 'd' => 'Never adjusting privacy settings'], 'correct' => 'a'],
                ['q' => 'Why might algorithms amplify emotionally charged or extreme content?', 'options' => ['a' => 'Because it tends to generate more engagement', 'b' => 'Because platforms are required by law to do so', 'c' => 'Because it is always factually accurate', 'd' => 'Because users specifically request it every time'], 'correct' => 'a'],
            ],
            'scenario' => [
                'prompt' => 'A friend keeps posting their daily location, school schedule, and home neighbourhood publicly on social media. They don\'t see the harm because "nothing bad has happened yet." What would you say to them, and what specific changes would you suggest?',
                'points' => [
                    ['explain the risk', 'strangers can track', 'predict your location', 'stalking'],
                    ['privacy settings', 'limit audience', 'friends only', 'restrict visibility'],
                    ['avoid posting in real time', 'post later', 'delay posting location'],
                    ['cumulative', 'small details add up', 'pattern'],
                    ['kindly', 'without judgment', 'supportive', 'caring'],
                ],
            ],
        ],

        6 => [
            'title' => 'Quest VI: Misinformation & Digital Literacy — Deep Dive',
            'topic' => 'Misinformation & Digital Literacy',
            'icon' => '🔍',
            'reading' => '
                <h3>Misinformation vs Disinformation vs Malinformation</h3>
                <p><strong>Misinformation</strong> is false information shared without intent to deceive (someone genuinely believes it and shares it). <strong>Disinformation</strong> is false information deliberately created and spread to deceive. <strong>Malinformation</strong> is genuine, true information shared out of context or with malicious intent to cause harm (like leaking a private fact to embarrass someone). Distinguishing these matters because the response differs: misinformation is corrected through education, while disinformation often requires tracing intent and source.</p>
                <h3>The Lateral Reading Technique</h3>
                <p>Professional fact-checkers rarely evaluate a claim by reading deeply on the same page. Instead they use <strong>lateral reading</strong>: opening new tabs to check what other, independent, trustworthy sources say about the same claim or source, before trusting anything. This is faster and more reliable than trying to judge a single page\'s credibility in isolation from its design or tone alone.</p>
                <h3>Spotting Manipulated Media</h3>
                <p><strong>Deepfakes</strong> use AI to generate realistic but fabricated video or audio of real people saying or doing things they never did. Warning signs include unnatural blinking or lighting, mismatched audio-lip sync, and content that seems designed purely to provoke outrage. Even without AI, simple editing tricks like cropping out context or pairing an old photo with a new, unrelated headline can mislead just as effectively.</p>
                <h3>Echo Chambers and Responsible Sharing</h3>
                <p>An <strong>echo chamber</strong> forms when someone is repeatedly exposed only to opinions that match their own, reinforcing existing beliefs and making opposing views feel more extreme or dishonest than they really are. Being a responsible digital citizen here means deliberately seeking out varied, credible sources, pausing before sharing anything that triggers a strong emotional reaction (that reaction is often the point), and checking a claim\'s original source before amplifying it further.</p>
            ',
            'objectives' => [
                ['q' => 'What is the key difference between misinformation and disinformation?', 'options' => ['a' => 'There is no difference', 'b' => 'Disinformation is deliberately created to deceive; misinformation is shared without that intent', 'c' => 'Misinformation is always true', 'd' => 'Disinformation only happens on TV'], 'correct' => 'b'],
                ['q' => '"Malinformation" refers to:', 'options' => ['a' => 'False information shared by accident', 'b' => 'Genuine information shared out of context to cause harm', 'c' => 'A computer virus', 'd' => 'Information that is always illegal'], 'correct' => 'b'],
                ['q' => 'What is "lateral reading"?', 'options' => ['a' => 'Reading a page top to bottom carefully', 'b' => 'Opening other tabs to check what independent sources say about a claim', 'c' => 'Reading only headlines', 'd' => 'A method for speed-reading long articles'], 'correct' => 'b'],
                ['q' => 'A "deepfake" is:', 'options' => ['a' => 'A verified news report', 'b' => 'AI-generated fake video or audio of a real person', 'c' => 'A type of encrypted message', 'd' => 'A backup copy of a video file'], 'correct' => 'b'],
                ['q' => 'Which is a warning sign of manipulated video content?', 'options' => ['a' => 'Consistent lighting throughout', 'b' => 'Unnatural blinking or mismatched audio-lip sync', 'c' => 'A clearly credited source', 'd' => 'A calm, factual tone'], 'correct' => 'b'],
                ['q' => 'An "echo chamber" is a situation where:', 'options' => ['a' => 'You are repeatedly exposed only to opinions matching your own', 'b' => 'You hear a variety of balanced viewpoints', 'c' => 'Your microphone has feedback', 'd' => 'News is fact-checked automatically'], 'correct' => 'a'],
                ['q' => 'Why should a strong emotional reaction to a post make you pause before sharing?', 'options' => ['a' => 'Emotional reactions are often exactly what manipulative content is designed to trigger', 'b' => 'Emotional posts are always accurate', 'c' => 'It has no relevance to accuracy', 'd' => 'You should always share emotional content immediately'], 'correct' => 'a'],
                ['q' => 'Simple (non-AI) misleading editing tricks include:', 'options' => ['a' => 'Cropping out context or pairing an old photo with a new headline', 'b' => 'Always citing the original source clearly', 'c' => 'Adding a timestamp to a video', 'd' => 'Publishing on a verified news outlet'], 'correct' => 'a'],
                ['q' => 'Before sharing a surprising claim, a responsible digital citizen should:', 'options' => ['a' => 'Share it immediately for engagement', 'b' => 'Check its original source and see what other credible outlets report', 'c' => 'Assume it is true because it looks convincing', 'd' => 'Ignore the source entirely'], 'correct' => 'b'],
                ['q' => 'Why is distinguishing misinformation from disinformation useful?', 'options' => ['a' => 'It changes the best way to respond - education vs tracing intent/source', 'b' => 'It has no practical use', 'c' => 'Because only disinformation is ever false', 'd' => 'Because misinformation is always intentional'], 'correct' => 'a'],
            ],
            'scenario' => [
                'prompt' => 'You see a viral post claiming a well-known public figure said something outrageous, with a screenshot as "proof." It has thousands of shares already. Walk through how you would verify this before deciding whether to share it.',
                'points' => [
                    ['lateral reading', 'check other sources', 'search independently', 'cross-check'],
                    ['original source', 'find the original', 'verify the screenshot'],
                    ['fact-check', 'fact-checking site', 'reputable outlet'],
                    ['pause', 'do not share immediately', 'wait before sharing'],
                    ['context', 'check for context', 'out of context'],
                ],
            ],
        ],

        7 => [
            'title' => 'Quest VII: Internet Governance Structures — Deep Dive',
            'topic' => 'Internet Governance Structures',
            'icon' => '🏛️',
            'reading' => '
                <h3>Revisiting the Multistakeholder Model in Practice</h3>
                <p>Internet governance works through overlapping layers rather than a single hierarchy. <strong>Technical governance</strong> keeps the network functioning (standards, addressing, naming). <strong>Legal/policy governance</strong> covers laws on data protection, cybercrime, and content. <strong>Economic governance</strong> covers competition, taxation, and trade related to digital services. Each layer involves different, sometimes overlapping, stakeholders - which is why global consensus can be slow but tends to be more broadly legitimate.</p>
                <h3>ICANN and the Domain Name System</h3>
                <p>ICANN (the Internet Corporation for Assigned Names and Numbers) coordinates the allocation of domain names and IP address blocks globally, working with regional registries and registrars. Its multistakeholder board includes technical experts, businesses, governments (in an advisory capacity), and civil society - deliberately avoiding control by any single country or company, which helps keep domain naming politically neutral and globally interoperable.</p>
                <h3>The Internet Governance Forum (IGF)</h3>
                <p>The IGF, convened under the United Nations, is an annual, non-binding discussion forum where governments, industry, academia, and civil society debate emerging Internet policy issues - from cybersecurity to AI regulation. Unlike a treaty body, the IGF produces no binding decisions; its value lies in surfacing consensus and shaping the norms that later inform national laws and international agreements.</p>
                <h3>Technical Standards Bodies and Regional Initiatives</h3>
                <p>Bodies like the IETF (network protocols), W3C (web standards), and IEEE (hardware/networking standards) ensure that a device built in one country can communicate with a device built in another. Meanwhile, regional and national Internet governance forums adapt global principles to local contexts - balancing universal interoperability with region-specific legal, cultural, and infrastructure realities. Civil society organisations and the private sector both play active roles here: civil society pushes for rights-respecting policy, while industry builds and operates most of the infrastructure the policies apply to.</p>
            ',
            'objectives' => [
                ['q' => 'Internet governance operates through:', 'options' => ['a' => 'A single global government', 'b' => 'Overlapping technical, legal/policy, and economic governance layers', 'c' => 'One private company', 'd' => 'No structure at all'], 'correct' => 'b'],
                ['q' => 'What does ICANN primarily coordinate?', 'options' => ['a' => 'Domain names and IP address allocation', 'b' => 'Social media content moderation', 'c' => 'National tax policy', 'd' => 'Mobile phone manufacturing'], 'correct' => 'a'],
                ['q' => 'Why is ICANN\'s multistakeholder board structure significant?', 'options' => ['a' => 'It avoids control by any single country or company', 'b' => 'It is fully controlled by one national government', 'c' => 'It only includes businesses', 'd' => 'It has no technical experts involved'], 'correct' => 'a'],
                ['q' => 'The Internet Governance Forum (IGF) is best described as:', 'options' => ['a' => 'A binding international treaty body', 'b' => 'A non-binding annual discussion forum under the UN', 'c' => 'A private corporation', 'd' => 'A national police agency'], 'correct' => 'b'],
                ['q' => 'What is the main value of the IGF, given it produces no binding decisions?', 'options' => ['a' => 'It has no real value', 'b' => 'Surfacing consensus and shaping norms that later inform laws and agreements', 'c' => 'Enforcing global Internet law directly', 'd' => 'Selling domain names'], 'correct' => 'b'],
                ['q' => 'The IETF is primarily responsible for:', 'options' => ['a' => 'Network protocol standards', 'b' => 'Domain name sales', 'c' => 'National cybersecurity law', 'd' => 'Social media moderation policy'], 'correct' => 'a'],
                ['q' => 'Why do technical standards bodies like the IETF, W3C, and IEEE matter?', 'options' => ['a' => 'They ensure devices from different countries/manufacturers can interoperate', 'b' => 'They set global tax rates', 'c' => 'They own all Internet infrastructure', 'd' => 'They replace national governments'], 'correct' => 'a'],
                ['q' => 'What role does civil society typically play in Internet governance?', 'options' => ['a' => 'Advocating for rights-respecting policy', 'b' => 'Owning most physical infrastructure', 'c' => 'Setting technical protocol standards exclusively', 'd' => 'Issuing domain names directly'], 'correct' => 'a'],
                ['q' => 'Why can global Internet governance decisions be slow?', 'options' => ['a' => 'Broad consensus across diverse stakeholders takes time but tends to be more legitimate', 'b' => 'There is no one working on it', 'c' => 'Only one person is in charge, causing bottlenecks', 'd' => 'Technology changes too rarely'], 'correct' => 'a'],
                ['q' => 'Regional Internet governance initiatives exist mainly to:', 'options' => ['a' => 'Adapt global principles to local legal, cultural, and infrastructure realities', 'b' => 'Replace ICANN entirely', 'c' => 'Ban international cooperation', 'd' => 'Operate independently of any global standards'], 'correct' => 'a'],
            ],
            'scenario' => [
                'prompt' => 'A national government proposes a law that would let it unilaterally control which websites are reachable within its borders, bypassing existing multistakeholder governance processes. Explain the trade-offs this raises and how a multistakeholder approach might respond.',
                'points' => [
                    ['multistakeholder', 'include civil society', 'include industry', 'broad consultation'],
                    ['open internet', 'access to information', 'free flow of information'],
                    ['sovereignty', 'legitimate security concerns', 'national interests'],
                    ['fragmentation', 'splinternet', 'interoperability risk'],
                    ['balance', 'transparency', 'accountability', 'checks and balances'],
                ],
            ],
        ],

        8 => [
            'title' => 'Quest VIII: Digital Rights, Ethics & the Future — Deep Dive',
            'topic' => 'Digital Rights, Ethics & the Future',
            'icon' => '⚖️',
            'reading' => '
                <h3>Digital Rights as Human Rights</h3>
                <p>The United Nations Human Rights Council has affirmed that the same rights people have offline - freedom of expression, privacy, association - must also be protected online. In practice this means digital rights include access to the Internet as an enabler of other rights (education, work, civic participation), freedom from unwarranted surveillance, the right to digital privacy, and freedom from discrimination by automated systems.</p>
                <h3>Ethics of AI and Emerging Technology</h3>
                <p>As AI systems increasingly make or influence decisions - loan approvals, hiring shortlists, content recommendations, even parole assessments - questions of <strong>algorithmic bias</strong> become urgent: if training data reflects historical inequality, the AI can reproduce or amplify it at scale. Key ethical principles being developed globally include <strong>transparency</strong> (people should be able to understand how automated decisions are made), <strong>accountability</strong> (someone must be responsible when AI causes harm), and <strong>fairness</strong> (systems should be tested for discriminatory outcomes before deployment).</p>
                <h3>The Digital Divide, Revisited</h3>
                <p>As digital rights and services expand, the digital divide risks becoming a rights divide - if essential services (banking, healthcare, government forms) move online-only, people without reliable access or digital literacy can be excluded from participation altogether. Bridging this gap increasingly requires not just infrastructure investment but also affordable devices, local-language content, and accessible design for people with disabilities.</p>
                <h3>Becoming a Responsible Digital Citizen for the Future</h3>
                <p>The Internet you helped shape today - through the accounts you keep secure, the misinformation you refuse to spread, the harassment you report, and the privacy you protect - is the Internet the next generation inherits. Being future-ready means staying curious about emerging technology, applying the same core principles (privacy, verification, respect, inclusion) to new tools as they appear, and remembering that governance of the digital world is not something that happens "elsewhere" - every user\'s daily choices are part of it.</p>
            ',
            'objectives' => [
                ['q' => 'What has the UN Human Rights Council affirmed about digital rights?', 'options' => ['a' => 'Online rights are unrelated to offline rights', 'b' => 'The same rights people have offline must be protected online', 'c' => 'Only governments have digital rights', 'd' => 'Digital rights do not exist'], 'correct' => 'b'],
                ['q' => '"Algorithmic bias" occurs when:', 'options' => ['a' => 'An AI system reproduces or amplifies inequality present in its training data', 'b' => 'A computer runs out of memory', 'c' => 'A website loads slowly', 'd' => 'A user changes their password'], 'correct' => 'a'],
                ['q' => 'Which is a key ethical principle for AI systems?', 'options' => ['a' => 'Secrecy about how decisions are made', 'b' => 'Transparency about how automated decisions are made', 'c' => 'No accountability for harmful outcomes', 'd' => 'Avoiding any testing before deployment'], 'correct' => 'b'],
                ['q' => 'Why might the digital divide become a "rights divide"?', 'options' => ['a' => 'Because essential services moving online-only can exclude those without access', 'b' => 'Because it has no connection to rights at all', 'c' => 'Because only entertainment moves online', 'd' => 'Because access is now universal everywhere'], 'correct' => 'a'],
                ['q' => 'Which factor helps bridge the digital divide beyond just infrastructure?', 'options' => ['a' => 'Local-language content and accessible design', 'b' => 'Restricting Internet access further', 'c' => 'Ignoring people with disabilities', 'd' => 'Removing affordable device options'], 'correct' => 'a'],
                ['q' => '"Accountability" in AI ethics means:', 'options' => ['a' => 'No one is responsible if AI causes harm', 'b' => 'Someone must be responsible when an AI system causes harm', 'c' => 'AI systems can never be evaluated', 'd' => 'Only users are responsible for AI errors'], 'correct' => 'b'],
                ['q' => 'Why is testing AI for "fairness" important before deployment?', 'options' => ['a' => 'To check for discriminatory outcomes across different groups', 'b' => 'To make the AI run faster', 'c' => 'To reduce the amount of training data needed', 'd' => 'It has no real importance'], 'correct' => 'a'],
                ['q' => 'What does it mean to be a "future-ready" digital citizen?', 'options' => ['a' => 'Applying core principles like privacy and respect to new technologies as they emerge', 'b' => 'Ignoring new technology entirely', 'c' => 'Assuming ethics only apply to old technology', 'd' => 'Waiting for governments to solve everything alone'], 'correct' => 'a'],
                ['q' => 'Which of these is an example of a digital right?', 'options' => ['a' => 'Freedom from unwarranted surveillance online', 'b' => 'The right to unlimited free data with no conditions', 'c' => 'The right to ignore all platform rules', 'd' => 'The right to access others\' private accounts'], 'correct' => 'a'],
                ['q' => 'Why does the reading suggest that governance of the digital world "happens" through everyday users?', 'options' => ['a' => 'Because daily choices like reporting harm and verifying information are part of shaping the Internet', 'b' => 'Because users have no influence on the Internet at all', 'c' => 'Because only governments shape the Internet', 'd' => 'Because governance only occurs in formal institutions'], 'correct' => 'a'],
            ],
            'scenario' => [
                'prompt' => 'A company wants to deploy an AI system to automatically shortlist job applicants, trained on ten years of the company\'s past hiring data. As a digital ethics advisor, what concerns would you raise and what would you recommend before deployment?',
                'points' => [
                    ['bias', 'historical bias', 'discrimination', 'unfair outcomes'],
                    ['test', 'audit', 'evaluate for fairness', 'bias testing'],
                    ['transparency', 'explainable', 'understand decisions'],
                    ['human oversight', 'human review', 'accountability', 'someone responsible'],
                    ['diverse data', 'representative data', 'inclusive training data'],
                ],
            ],
        ],

    ];
}
