# AI-Powered Support Ticket System — Backend

A PHP + MySQL REST API backend for a Support Ticket System with Google Gemini AI integration.

## Requirements

- **PHP** 7.4+ (with `pdo_mysql` and `curl` extensions)
- **MySQL** 5.7+ / MariaDB 10.3+
- **XAMPP** (or any Apache/MySQL/PHP stack)

## Setup

### 1. Create the Database

Open a terminal or phpMyAdmin and run:

```sql
CREATE DATABASE support_ticket_system;
USE support_ticket_system;
```

Then run the schema and seed files:

```bash
cd backend
C:\xampp\mysql\bin\mysql -u root < schema.sql
C:\xampp\mysql\bin\mysql -u root support_ticket_system < schema.sql
C:\xampp\mysql\bin\mysql -u root support_ticket_system < seed.sql
```

### 2. Configure Gemini API Key

Edit `config/gemini.php` and replace `YOUR_GEMINI_API_KEY_HERE` with your actual Google Gemini API key.

Or set it as an environment variable:
```bash
set GEMINI_API_KEY=your_key_here
```

### 3. Start the Server

Using PHP's built-in server:
```bash
C:\xampp\php\php -S localhost:8000 -t d:\internship\backend
```

Or place the `backend` folder in `C:\xampp\htdocs\` and access via `http://localhost/backend/api/...`

### 4. Test the API

```bash
# List all tickets
curl http://localhost:8000/api/tickets

# Get single ticket
curl http://localhost:8000/api/tickets/1

# Create a ticket
curl -X POST http://localhost:8000/api/tickets \
  -F "title=Test Ticket" \
  -F "description=This is a test" \
  -F "priority=High" \
  -F "customer_name=John Doe" \
  -F "customer_email=john@test.com"

# Update ticket status
curl -X PUT http://localhost:8000/api/tickets/1 \
  -H "Content-Type: application/json" \
  -d '{"status": "In Progress"}'

# Add reply
curl -X POST http://localhost:8000/api/tickets/1/replies \
  -H "Content-Type: application/json" \
  -d '{"author_role": "agent", "author_name": "Sarah", "message": "We are looking into it"}'

# Regenerate AI reply
curl -X POST http://localhost:8000/api/tickets/1/ai-reply

# List agents
curl http://localhost:8000/api/agents

# Analytics summary
curl http://localhost:8000/api/analytics/summary
```

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/tickets` | List tickets (optional filters: status, category, priority, agent_id, customer_email) |
| POST | `/api/tickets` | Create ticket (multipart form-data) |
| GET | `/api/tickets/{id}` | Get single ticket |
| PUT | `/api/tickets/{id}` | Update ticket (status, agent_id, priority) |
| POST | `/api/tickets/{id}/replies` | Add reply to ticket |
| POST | `/api/tickets/{id}/ai-reply` | Regenerate AI-suggested reply |
| GET | `/api/agents` | List agents with performance stats |
| GET | `/api/analytics/summary` | Aggregated analytics data |

## Project Structure

```
backend/
├── config/
│   ├── db.php            # MySQL PDO connection
│   └── gemini.php        # Gemini API key + helper functions
├── api/
│   ├── helpers.php       # Shared ticket response builder
│   ├── tickets.php       # GET/POST /api/tickets
│   ├── ticket_detail.php # GET/PUT /api/tickets/{id}
│   ├── ticket_replies.php# POST /api/tickets/{id}/replies
│   ├── ticket_ai_reply.php# POST /api/tickets/{id}/ai-reply
│   ├── agents.php        # GET /api/agents
│   └── analytics.php     # GET /api/analytics/summary
├── uploads/              # Ticket file attachments
├── index.php             # Central API router
├── .htaccess             # Apache URL rewriting
├── schema.sql            # Database schema
├── seed.sql              # Sample data
└── README.md             # This file
```
