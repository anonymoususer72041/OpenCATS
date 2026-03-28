<?php
/*
 * CATS
 * Update 371 - cleanup orphaned entity records and references
 */

function update_371($db)
{
    $queries = array(
        /* Remove orphaned entity-owned activity rows. */
        "DELETE FROM activity
         WHERE data_item_type = 100
           AND NOT EXISTS (
                SELECT 1
                FROM candidate
                WHERE candidate.candidate_id = activity.data_item_id
                  AND candidate.site_id = activity.site_id
           )",
        "DELETE FROM activity
         WHERE data_item_type = 200
           AND NOT EXISTS (
                SELECT 1
                FROM company
                WHERE company.company_id = activity.data_item_id
                  AND company.site_id = activity.site_id
           )",
        "DELETE FROM activity
         WHERE data_item_type = 300
           AND NOT EXISTS (
                SELECT 1
                FROM contact
                WHERE contact.contact_id = activity.data_item_id
                  AND contact.site_id = activity.site_id
           )",
        "DELETE FROM activity
         WHERE data_item_type = 400
           AND NOT EXISTS (
                SELECT 1
                FROM joborder
                WHERE joborder.joborder_id = activity.data_item_id
                  AND joborder.site_id = activity.site_id
           )",

        /* Remove orphaned entity-owned calendar rows. */
        "DELETE FROM calendar_event
         WHERE data_item_type = 100
           AND NOT EXISTS (
                SELECT 1
                FROM candidate
                WHERE candidate.candidate_id = calendar_event.data_item_id
                  AND candidate.site_id = calendar_event.site_id
           )",
        "DELETE FROM calendar_event
         WHERE data_item_type = 200
           AND NOT EXISTS (
                SELECT 1
                FROM company
                WHERE company.company_id = calendar_event.data_item_id
                  AND company.site_id = calendar_event.site_id
           )",
        "DELETE FROM calendar_event
         WHERE data_item_type = 300
           AND NOT EXISTS (
                SELECT 1
                FROM contact
                WHERE contact.contact_id = calendar_event.data_item_id
                  AND contact.site_id = calendar_event.site_id
           )",
        "DELETE FROM calendar_event
         WHERE data_item_type = 400
           AND NOT EXISTS (
                SELECT 1
                FROM joborder
                WHERE joborder.joborder_id = calendar_event.data_item_id
                  AND joborder.site_id = calendar_event.site_id
           )",

        /* Remove orphaned candidate-related rows. */
        "DELETE FROM candidate_tag
         WHERE NOT EXISTS (
                SELECT 1
                FROM candidate
                WHERE candidate.candidate_id = candidate_tag.candidate_id
                  AND candidate.site_id = candidate_tag.site_id
           )",
        "DELETE FROM career_portal_questionnaire_history
         WHERE NOT EXISTS (
                SELECT 1
                FROM candidate
                WHERE candidate.candidate_id = career_portal_questionnaire_history.candidate_id
                  AND candidate.site_id = career_portal_questionnaire_history.site_id
           )",
        "DELETE FROM candidate_duplicates
         WHERE NOT EXISTS (
                SELECT 1
                FROM candidate
                WHERE candidate.candidate_id = candidate_duplicates.old_candidate_id
                  AND candidate.site_id = candidate_duplicates.site_id
           )
            OR NOT EXISTS (
                SELECT 1
                FROM candidate
                WHERE candidate.candidate_id = candidate_duplicates.new_candidate_id
                  AND candidate.site_id = candidate_duplicates.site_id
           )",

        /* Remove orphaned company-related rows. */
        "DELETE FROM company_department
         WHERE NOT EXISTS (
                SELECT 1
                FROM company
                WHERE company.company_id = company_department.company_id
                  AND company.site_id = company_department.site_id
           )",

        /* Remove orphaned pipeline rows. */
        "DELETE FROM candidate_joborder
         WHERE NOT EXISTS (
                SELECT 1
                FROM candidate
                WHERE candidate.candidate_id = candidate_joborder.candidate_id
                  AND candidate.site_id = candidate_joborder.site_id
           )
            OR NOT EXISTS (
                SELECT 1
                FROM joborder
                WHERE joborder.joborder_id = candidate_joborder.joborder_id
                  AND joborder.site_id = candidate_joborder.site_id
           )",
        "DELETE FROM candidate_joborder_status_history
         WHERE NOT EXISTS (
                SELECT 1
                FROM candidate
                WHERE candidate.candidate_id = candidate_joborder_status_history.candidate_id
                  AND candidate.site_id = candidate_joborder_status_history.site_id
           )
            OR NOT EXISTS (
                SELECT 1
                FROM joborder
                WHERE joborder.joborder_id = candidate_joborder_status_history.joborder_id
                  AND joborder.site_id = candidate_joborder_status_history.site_id
           )",

        /* Remove orphaned saved list entries by site-scoped data item. */
        "DELETE FROM saved_list_entry
         WHERE data_item_type = 100
           AND NOT EXISTS (
                SELECT 1
                FROM candidate
                WHERE candidate.candidate_id = saved_list_entry.data_item_id
                  AND candidate.site_id = saved_list_entry.site_id
           )",
        "DELETE FROM saved_list_entry
         WHERE data_item_type = 200
           AND NOT EXISTS (
                SELECT 1
                FROM company
                WHERE company.company_id = saved_list_entry.data_item_id
                  AND company.site_id = saved_list_entry.site_id
           )",
        "DELETE FROM saved_list_entry
         WHERE data_item_type = 300
           AND NOT EXISTS (
                SELECT 1
                FROM contact
                WHERE contact.contact_id = saved_list_entry.data_item_id
                  AND contact.site_id = saved_list_entry.site_id
           )",
        "DELETE FROM saved_list_entry
         WHERE data_item_type = 400
           AND NOT EXISTS (
                SELECT 1
                FROM joborder
                WHERE joborder.joborder_id = saved_list_entry.data_item_id
                  AND joborder.site_id = saved_list_entry.site_id
           )",

        /* Reset orphaned references to the current no-link sentinel. */
        "UPDATE activity
         SET joborder_id = -1
         WHERE NOT EXISTS (
                SELECT 1
                FROM joborder
                WHERE joborder.joborder_id = activity.joborder_id
                  AND joborder.site_id = activity.site_id
           )",
        "UPDATE calendar_event
         SET joborder_id = -1
         WHERE NOT EXISTS (
                SELECT 1
                FROM joborder
                WHERE joborder.joborder_id = calendar_event.joborder_id
                  AND joborder.site_id = calendar_event.site_id
           )",
        "UPDATE joborder
         SET contact_id = -1
         WHERE NOT EXISTS (
                SELECT 1
                FROM contact
                WHERE contact.contact_id = joborder.contact_id
                  AND contact.site_id = joborder.site_id
           )",
        "UPDATE company
         SET billing_contact = -1
         WHERE NOT EXISTS (
                SELECT 1
                FROM contact
                WHERE contact.contact_id = company.billing_contact
                  AND contact.site_id = company.site_id
           )",
        "UPDATE contact
         SET reports_to = -1
         WHERE NOT EXISTS (
                SELECT 1
                FROM contact AS reports_to_contact
                WHERE reports_to_contact.contact_id = contact.reports_to
                  AND reports_to_contact.site_id = contact.site_id
           )"
    );

    foreach ($queries as $query)
    {
        $db->query($query);
    }
}

?>
