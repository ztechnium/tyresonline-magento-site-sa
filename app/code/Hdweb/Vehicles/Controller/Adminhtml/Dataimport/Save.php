<?php

namespace Hdweb\Vehicles\Controller\Adminhtml\Dataimport;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Image\AdapterFactory;
use Magento\Store\Model\ScopeInterface; 


class Save extends \Magento\Backend\App\Action
{

    protected $fileSystem;

    protected $uploaderFactory;

    protected $request;

    protected $adapterFactory;


    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        AdapterFactory $adapterFactory

    ) {
        parent::__construct($context);
        $this->fileSystem = $fileSystem;
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->adapterFactory = $adapterFactory;
        $this->uploaderFactory = $uploaderFactory;
    }

    public function execute()
    { 

         if ( (isset($_FILES['importdata']['name'])) && ($_FILES['importdata']['name'] != '') ) 
         {
            try 
           {    
                $uploaderFactory = $this->uploaderFactory->create(['fileId' => 'importdata']);
                $uploaderFactory->setAllowedExtensions(['csv', 'xls']);
                $uploaderFactory->setAllowRenameFiles(true);
                $uploaderFactory->setFilesDispersion(true);

                $mediaDirectory = $this->fileSystem->getDirectoryRead(DirectoryList::MEDIA);
                $destinationPath = $mediaDirectory->getAbsolutePath('hdweb_vehicles_IMPORTDATA');

                $result = $uploaderFactory->save($destinationPath);

                if (!$result) 
                   {
                     throw new LocalizedException
                     (
                        __('File cannot be saved to path: $1', $destinationPath)
                     );

                   }
                else
                    {   
                        $imagePath = 'hdweb_vehicles_IMPORTDATA'.$result['file'];

                        $mediaDirectory = $this->fileSystem->getDirectoryRead(DirectoryList::MEDIA);

                        $destinationfilePath = $mediaDirectory->getAbsolutePath($imagePath);

                        /* file read operation */

                        $f_object = fopen($destinationfilePath, "r");

                        $column = fgetcsv($f_object);
                        

                        // column name must be same as the Sample file name 

                        if($f_object)
                        {
                            /* if( ($column[0] == 'ID') && ($column[1] == 'Make') && ($column[2] == 'Model') && ($column[3] == 'Store View') && ($column[4] == 'Make Paragraph 1') && ($column[5] == 'Make Paragraph 2') && ($column[6] == 'Model Paragraph 1') && ($column[7] == 'Model Paragraph 2') && ($column[8] == 'Model Paragraph 3') && ($column[9] == 'Meta Title') && ($column[10] == 'Meta Keywords') && ($column[11] == 'Meta Description') )
                            { */   

                                $count = 0;

                                while (($columns = fgetcsv($f_object)) !== FALSE) 
                                {

                                    $rowData = $this->_objectManager->create('Hdweb\Vehicles\Model\Vehicles');

                                    if($columns[0] != 'ID')// unique Name like Primary key
                                    {   
                                        $count++;

                                    /// here this are all the Getter Setter Method which are call to set value 
                                    // the auto increment column name not used to set value 
                                        if($columns[0] != ''){
                                            $rowData->load($columns[0]);
                                        }
                                        

                                        $rowData->setMake($columns[1]);

                                        $rowData->setModel($columns[2]);

                                        $rowData->setStoreId($columns[3]);

                                        $rowData->setData('make_paragraph1',$columns[4]);

                                        $rowData->setData('make_paragraph2',$columns[5]);

                                        $rowData->setData('model_paragraph1',$columns[6]);

                                        $rowData->setData('model_paragraph2',$columns[7]);

                                        $rowData->setData('model_paragraph3',$columns[8]);

                                        $rowData->setMetaTitle($columns[9]);

                                        $rowData->setMetaKeywords($columns[10]);

                                        //$rowData->setMetaDecription($columns[11]);
                                        $rowData->setData('meta_description',$columns[11]);

                                        $rowData->save();   

                                    }

                                } 

                            $this->messageManager->addSuccess(__('A total of %1 record(s) have been Added/Updated.', $count));
                            $this->_redirect('hdweb_vehicles/items/index');
                            /* }
                            else
                            {
                                $this->messageManager->addError(__("invalid Formated File"));
                                $this->_redirect('hdweb_vehicles/dataimport/importdata');
                            } */

                        } 
                        else
                        {
                            $this->messageManager->addError(__("File hase been empty"));
                            $this->_redirect('hdweb_vehicles/dataimport/importdata');
                        }

                    }                   

           } 
           catch (\Exception $e) 
          {   
               $this->messageManager->addError(__($e->getMessage()));
               $this->_redirect('hdweb_vehicles/dataimport/importdata');
          }

         }
         else
         {
            $this->messageManager->addError(__("Please try again."));
            $this->_redirect('hdweb_vehicles/dataimport/importdata');
         }
    }
}