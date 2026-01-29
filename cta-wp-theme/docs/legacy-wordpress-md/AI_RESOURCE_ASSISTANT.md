# AI Assistant for Resource Uploads

## ✅ Feature Added!

You can now use the **AI Assistant** button when creating resources to automatically generate:
- ✨ Resource title
- ✨ Detailed description
- ✨ Short excerpt
- ✨ Email subject line
- ✨ Email body with placeholders
- ✨ Suggested icon class

---

## How It Works

### Step 1: Start Creating a Resource

1. Go to **Resources → Add New**
2. (Optional) Upload your file to Media Library first and enter the Attachment ID
3. Look for the **AI Assistant** button in the blue info box at the top

### Step 2: Click AI Assistant

The button looks like this:

```
┌────────────────────────┐
│ 🤖 AI Assistant        │
│                        │
│ Generate title,        │
│ description & email    │
└────────────────────────┘
```

### Step 3: AI Generates Content

The AI will automatically fill in:

- **Title field** - Clear, professional title (if empty)
- **Description** - 2-3 paragraphs explaining the resource
- **Excerpt** - Short preview text for cards
- **Email Subject** - Professional subject line
- **Email Body** - Complete email with placeholders
- **Icon** - Suggested Font Awesome icon class

### Step 4: Review and Edit

- ✅ All content is editable - the AI provides a starting point
- ✅ Review for accuracy and tone
- ✅ Customize to match your brand voice
- ✅ Add any specific details the AI might have missed

---

## What the AI Considers

The AI generates content based on:

1. **File name** (if you've entered an Attachment ID)
2. **Care training context** - knows this is for care sector professionals
3. **Professional tone** - appropriate for CQC-compliant training
4. **Best practices** - includes proper email placeholders

---

## Example Output

### For a file named "CQC-Training-Requirements-Checklist.pdf":

**Title:**
```
CQC Training Requirements Checklist
```

**Description:**
```
This comprehensive checklist outlines all mandatory training requirements 
for CQC-registered care services. Use it to ensure your team has the right 
training to meet regulatory standards and maintain compliance.

The checklist covers fundamental training areas including safeguarding, 
health and safety, infection control, and person-centred care. Each section 
includes guidance on frequency and evidence requirements for CQC inspections.

Perfect for care managers, training coordinators, and registered managers 
who need to plan and track their team's training compliance.
```

**Excerpt:**
```
Comprehensive checklist of mandatory training requirements for CQC compliance. 
Covers all essential training areas with guidance on frequency and evidence.
```

**Email Subject:**
```
Your CQC Training Requirements Checklist from Continuity Training Academy
```

**Email Body:**
```
Hi {{first_name}},

Thank you for requesting the CQC Training Requirements Checklist.

Download your resource here:
{{download_link}}

This link expires in {{expiry_days}} days.

This checklist will help you ensure your team has all the mandatory training 
needed for CQC compliance. If you have any questions about training 
requirements, our team is here to help.

Best regards,
Continuity Training Academy
```

**Icon:**
```
fas fa-clipboard-check
```

---

## AI Settings

The AI Assistant uses your configured AI provider (Groq, Anthropic, or OpenAI).

### To configure:
1. Go to **Settings → AI Assistant**
2. Enter your API key
3. Select your preferred provider
4. Save settings

### Supported providers:
- **Groq** (fast, cost-effective)
- **Anthropic Claude** (high quality)
- **OpenAI** (GPT models)

---

## Benefits

### For You:
- ⚡ **Saves time** - no more writing from scratch
- 📝 **Consistent quality** - professional tone every time
- 🎯 **Proper formatting** - includes all necessary placeholders
- 💡 **Smart suggestions** - icon classes that match file types

### For Your Workflow:
- **Faster resource creation** - from 15 minutes to 2 minutes
- **Better consistency** - all resources follow the same structure
- **Less writer's block** - always have a starting point
- **Professional results** - even for non-writers

---

## Tips for Best Results

### 1. Upload the File First
If you upload your file to the Media Library and enter the Attachment ID **before** clicking AI Assistant, the AI can use the filename to generate more relevant content.

### 2. Review the Icon
The AI suggests an icon based on the file type:
- `fas fa-file-pdf` for PDFs
- `fas fa-file-excel` for spreadsheets
- `fas fa-clipboard-check` for checklists
- `fas fa-table` for matrices/trackers

You can change this to any Font Awesome icon.

### 3. Customize the Email
The AI includes the required placeholders, but you can:
- Add more personality
- Include specific instructions
- Add links to related courses
- Mention your support contact

### 4. Edit the Description
The AI provides a good starting point, but you might want to:
- Add specific details about your service
- Include any unique features
- Mention related training courses
- Add calls-to-action

---

## Technical Details

### How It Works:

```
┌────────────────────────────────────────────────────────────┐
│ 1. USER CLICKS "AI ASSISTANT"                              │
│    - Button shows loading state                            │
│    - Collects file name if available                       │
└────────────────────────────────────────────────────────────┘
                            ↓
┌────────────────────────────────────────────────────────────┐
│ 2. AJAX REQUEST TO SERVER                                  │
│    - Sends file name and context                           │
│    - Includes security nonce                               │
└────────────────────────────────────────────────────────────┘
                            ↓
┌────────────────────────────────────────────────────────────┐
│ 3. SERVER CALLS AI PROVIDER                                │
│    - Builds prompt with care training context              │
│    - Requests JSON-formatted response                      │
│    - Includes all required fields                          │
└────────────────────────────────────────────────────────────┘
                            ↓
┌────────────────────────────────────────────────────────────┐
│ 4. AI GENERATES CONTENT                                    │
│    - Creates title, description, excerpt                   │
│    - Writes email subject and body                         │
│    - Suggests appropriate icon                             │
└────────────────────────────────────────────────────────────┘
                            ↓
┌────────────────────────────────────────────────────────────┐
│ 5. JAVASCRIPT FILLS FIELDS                                 │
│    - Populates title (if empty)                            │
│    - Fills description in editor                           │
│    - Sets excerpt, email fields, icon                      │
│    - Shows success message                                 │
└────────────────────────────────────────────────────────────┘
```

### Security:
- ✅ Nonce verification for AJAX requests
- ✅ Capability checks (must be able to edit posts)
- ✅ Input sanitization
- ✅ Only fills empty fields (won't overwrite existing content)

---

## Troubleshooting

### Button doesn't appear
- Make sure you're on the "Add New Resource" page
- The button only shows for new resources (not when editing existing ones)

### "AI functionality not available" error
- Go to **Settings → AI Assistant**
- Make sure you've entered an API key
- Check that your API key is valid

### Generated content is generic
- Upload the file first so the AI can use the filename
- Use descriptive file names (e.g., "CQC-Training-Checklist.pdf" not "document1.pdf")
- Edit the generated content to add specific details

### Content doesn't fill in
- Check browser console for JavaScript errors
- Make sure you have a modern browser
- Try refreshing the page

### AI generates wrong icon
- The AI makes its best guess based on the file name
- You can always change it manually to any Font Awesome icon
- Common icons: `fas fa-file-pdf`, `fas fa-file-excel`, `fas fa-clipboard-check`

---

## Files Modified

1. **`wordpress-theme/inc/resource-downloads.php`**
   - Added AI Assistant button to admin notice
   - Added JavaScript for AJAX handling
   - Added `cta_ajax_generate_resource_content()` AJAX handler
   - Integrated with existing AI provider system

---

## Result

**Before**: Manual content creation for every resource  
**After**: One-click AI generation with smart suggestions

✅ **Faster workflow**  
✅ **Professional quality**  
✅ **Consistent formatting**  
✅ **Proper placeholders**  
✅ **Smart icon suggestions**
