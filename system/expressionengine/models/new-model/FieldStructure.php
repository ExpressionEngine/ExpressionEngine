<?php
/**
 * Field Structure Interface
 *
 * Defines a structure of a field and stores its settings.
 */
interface FieldStructure {

    /**
     * Display the settings form for this field
     *
     * @return String   Settings form html
     */
    public function displaySettings();

    /**
     * Save the setting data for this field
     *
     * Should call validateSettings() before saving
     *
     * @return void
     */
    public function saveSettings();

    /**
     * Validate the setting data for this field
     *
     * @throws FieldStructureInvalidException if missing / invalid data
     * @return void
     */
    public function validateSettings();

    /**
     * Display the settings form for this field
     *
     * @param FieldContent   $field_content   An object implementing the FieldContent interface
     * @return String   HTML for the entry / edit form
     */
    public function form($field_content);

    /**
     * Delete settings and all content for this field
     *
     * @return void
     */
    public function delete();
}