## Requirements

### Requirement: Ticketing

The system SHALL support creating, assigning, tracking, and resolving customer-service tickets through a structured lifecycle linked to WhatsApp chats, customers, and instansi, with status, priority, category, assignment, progress notes, file attachments, and attachment tracking.

#### Scenario: Agent creates ticket

- GIVEN a customer chat requires escalation
- WHEN an agent with `ticket.manage` permission creates a ticket
- THEN the system SHALL persist the ticket header and detail data in `TTicket` with a unique `NomorTicket`
- AND the system SHALL record the creating user in `DibuatOleh`
- AND the system SHALL record an activity entry in `TTicketD`
- AND the system SHALL allow status, priority, category, assignment, progress notes, and attachment tracking

#### Scenario: Ticket appears in operational view

- WHEN a ticket is created or updated
- THEN authorized users SHALL be able to view its latest status from the Ticketing module

#### Scenario: Agent creates ticket from chat

- GIVEN an agent has `ticket.manage` permission
- WHEN the agent creates a ticket linked to a chat session
- THEN the system SHALL set `IdChat` on the ticket
- AND the system SHALL prefill customer/instansi from the chat mapping when available

#### Scenario: Supervisor assigns ticket

- GIVEN a ticket exists with a non-final status
- WHEN a supervisor with `ticket.manage` assigns the ticket to a pengguna
- THEN the system SHALL update `DitugaskanKepada` and `TglDitugaskan`
- AND the system SHALL record the reassignment in `TTicketDPenugasan` with `DitugaskanDari`, `DitugaskanKepada`, and `AlasanPenugasan`
- AND the system SHALL record an activity entry in `TTicketD`
- AND the system SHALL notify the newly assigned pengguna

#### Scenario: Ticket status changes

- WHEN a ticket status is changed
- THEN the system SHALL record `StatusSebelum` and `StatusSesudah` in `TTicketD`
- AND the system SHALL update `TglEdit` and `DieditOleh`

#### Scenario: Ticket reaches final status

- WHEN a ticket is set to a status flagged `StatusFinal` in `MStatusTicket`
- THEN the system SHALL set `TglDitutup` and `DitutupOleh`
- AND the ticket SHALL remain visible in history

#### Scenario: Ticket overdue SLA

- GIVEN a ticket has `TglTargetSelesai` in the past and a non-final status
- THEN the system SHALL flag the ticket as overdue in the dashboard and ticket table

#### Scenario: Agent views tickets assigned to them

- WHEN an agent with `ticket.view` applies the "my tickets" filter
- THEN the system SHALL list only tickets whose `DitugaskanKepada` equals the current user

#### Scenario: Agent writes progress note

- GIVEN a ticket exists and an agent has `ticket.manage` permission
- WHEN the agent writes a new progress note during pengerjaan
- THEN the system SHALL persist the note as an activity entry in `TTicketD` with `JenisAktivitas = Catatan`
- AND the system SHALL record the author in `DibuatOleh` and timestamp in `TglAktivitas`
- AND the note SHALL appear in the ticket activity timeline

#### Scenario: Agent uploads ticket attachments

- GIVEN an agent has `ticket.manage` permission
- WHEN the agent uploads one or more files to a ticket
- THEN the system SHALL store each file and record its metadata in `TTicketDLampiran`
- AND the system SHALL reject any file larger than 3 MB
- AND the system SHALL allow multiple files per ticket

#### Scenario: Agent downloads ticket attachment

- GIVEN an authenticated user has `ticket.view` permission
- WHEN the user requests a ticket attachment download
- THEN the system SHALL verify the user permission
- AND the system SHALL stream the original file with its stored name

#### Scenario: Agent attaches file to ticket

- GIVEN an agent has `ticket.manage` permission
- WHEN the agent attaches a file to a ticket
- THEN the system SHALL store the attachment metadata in `TTicketDLampiran`

#### Scenario: Master ticket data managed

- WHEN an administrator updates `MStatusTicket`, `MKategoriTicket`, or `MPrioritasTicket`
- THEN the ticket forms and filters SHALL reflect the updated active master data

#### Scenario: Ticketing dashboard shows real statistics

- WHEN an agent with `ticket.view` opens the Ticketing page
- THEN the system SHALL display counts of new, in-progress, overdue, and completed tickets computed from `TTicket`
- AND the system SHALL display a recent ticket queue from `TTicket`
- AND the system SHALL provide actions to create and manage tickets

#### Scenario: User without ticket manage permission

- WHEN a user with only `ticket.view` attempts to create or edit a ticket
- THEN the system SHALL deny the create/edit action

### Requirement: Task Management

The system SHALL provide a task module to manage action items that may be linked to tickets, chats, and customers, with assignment, reassignment history, progress notes/comments, file attachments, checklists, priority, due dates, and status tracking.

#### Scenario: User creates standalone task

- GIVEN a user has `task.manage` permission
- WHEN the user creates a task
- THEN the system SHALL persist the task in `TTask` with a unique `NomorTask`
- AND the system SHALL record the creating user in `DibuatOleh`

#### Scenario: User creates task linked to ticket

- WHEN a user creates a task with `IdTicket` set
- THEN the system SHALL link the task to the ticket
- AND the task SHALL appear as an action item in the ticket detail

#### Scenario: User assigns task

- GIVEN a task exists
- WHEN a user with `task.manage` assigns the task to a pengguna
- THEN the system SHALL update `DitugaskanKepada` and `TglDitugaskan`
- AND the system SHALL record the assignment in `TTaskDPenugasan` with `DitugaskanDari`, `DitugaskanKepada`, and `AlasanPenugasan`
- AND the system SHALL notify the newly assigned pengguna

#### Scenario: User reassigns task

- GIVEN a task is already assigned to a pengguna
- WHEN a user with `task.manage` reassigns the task to a different pengguna
- THEN the system SHALL record the previous and new assignee in a new `TTaskDPenugasan` row
- AND the previous assignment history SHALL remain visible in the task detail

#### Scenario: User writes task progress note

- GIVEN a task exists and a user has `task.manage` permission
- WHEN the user writes a new note/comment during pengerjaan
- THEN the system SHALL persist the note in `TTaskDKomentar` with `TglKomentar`
- AND the note SHALL appear in the task comment timeline

#### Scenario: User uploads task attachments

- GIVEN a user has `task.manage` permission
- WHEN the user uploads one or more files to a task
- THEN the system SHALL store each file and record its metadata in `TTaskDLampiran`
- AND the system SHALL reject any file larger than 3 MB
- AND the system SHALL allow multiple files per task

#### Scenario: User downloads task attachment

- GIVEN an authenticated user has `task.view` permission
- WHEN the user requests a task attachment download
- THEN the system SHALL verify the user permission
- AND the system SHALL stream the original file with its stored name

#### Scenario: User toggles checklist item

- GIVEN a task has checklist items in `TTaskDChecklist`
- WHEN a user with `task.manage` toggles an item
- THEN the system SHALL update `Selesai`, `TglSelesai`, and `DiselesaikanOleh`

#### Scenario: Task overdue

- GIVEN a task has `TglTargetSelesai` in the past and a non-final status
- THEN the system SHALL flag the task as overdue in the task table

#### Scenario: User views tasks assigned to them

- WHEN a user with `task.view` applies the "my tasks" filter
- THEN the system SHALL list only tasks whose `DitugaskanKepada` equals the current user

#### Scenario: Master task status managed

- WHEN an administrator updates `MStatusTask`
- THEN the task forms and filters SHALL reflect the updated active status data

#### Scenario: User without task permission

- WHEN a user without `task.view` opens the admin panel
- THEN the system SHALL not show the task menu
- AND the system SHALL deny access to task resources
