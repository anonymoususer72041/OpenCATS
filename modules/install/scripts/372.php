<?php
/*
 * CATS
 * Update 372 - sanitize attachment stored filenames
 *
 * Copyright (C) 2005 - 2007 Cognizo Technologies, Inc.
 *
 * The contents of this file are subject to the CATS Public License
 * Version 1.1a (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.catsone.com/.
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "CATS Standard Edition".
 *
 * The Initial Developer of the Original Code is Cognizo Technologies, Inc.
 * Portions created by the Initial Developer are Copyright (C) 2005 - 2007
 * (or from the year in which this file was created to the year 2007) by
 * Cognizo Technologies, Inc. All Rights Reserved.
 */

include_once('lib/FileUtility.php');

function update_372($db)
{
    $rs = $db->getAllAssoc(
        "SELECT
            attachment_id,
            directory_name,
            stored_filename
        FROM
            attachment
        ORDER BY
            attachment_id ASC"
    );

    foreach ($rs as $index => $data)
    {
        $oldFilename = $data['stored_filename'];
        $newFilename = FileUtility::makeSafeFilename($oldFilename);

        if ($newFilename == $oldFilename)
        {
            continue;
        }

        $directoryPath = 'attachments/' . $data['directory_name'];
        $oldFilePath = $directoryPath . '/' . $oldFilename;

        if (!file_exists($oldFilePath))
        {
            continue;
        }

        $targetFilename = $newFilename;
        $targetFilePath = $directoryPath . '/' . $targetFilename;

        if (file_exists($targetFilePath))
        {
            $fileExtension = FileUtility::getFileExtension($newFilename);
            if ($fileExtension == '')
            {
                $baseFilename = $newFilename;
            }
            else
            {
                $baseFilename = substr(
                    $newFilename,
                    0,
                    strlen($newFilename) - strlen($fileExtension) - 1
                );
            }

            $targetFilePath = '';
            for ($i = 1; $i < 1000; $i++)
            {
                if ($fileExtension == '')
                {
                    $candidateFilename = $baseFilename . '_' . $i;
                }
                else
                {
                    $candidateFilename = $baseFilename . '_' . $i . '.' . $fileExtension;
                }

                $candidateFilePath = $directoryPath . '/' . $candidateFilename;
                if (!file_exists($candidateFilePath))
                {
                    $targetFilename = $candidateFilename;
                    $targetFilePath = $candidateFilePath;
                    break;
                }
            }

            if ($targetFilePath == '')
            {
                continue;
            }
        }

        if (@rename($oldFilePath, $targetFilePath))
        {
            $db->query(
                "UPDATE
                    attachment
                 SET
                    stored_filename = " . $db->makeQueryString($targetFilename) . "
                 WHERE
                    attachment_id = " . $data['attachment_id']
            );
        }
    }
}

?>
