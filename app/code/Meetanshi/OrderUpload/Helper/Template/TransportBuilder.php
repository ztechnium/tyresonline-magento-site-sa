<?php

namespace Meetanshi\OrderUpload\Helper\Template;

use Magento\Framework\Mail\Template\TransportBuilder as TBuilder;
use Magento\Framework\App\ObjectManager;

class TransportBuilder extends TBuilder
{
    /**
     * @var array
     */
    private $parts = [];
    /**
     * @var
     */
    protected $objectManager;
    /**
     * @var array
     */
    protected $attachments = [];
    /**
     * @var array
     */
    private $messageData = [];
    /**
     * @var
     */
    protected $partFactory;

    protected $message;

    /**
     * @param $body
     * @param null $filename
     * @param string $fileType
     * @return $this
     */
    public function addAttachment($body, $filename = null, $fileType = 'application/octet-stream')
    {
        $objectManager = ObjectManager::getInstance();
        $version = $this->getVersion();
        $this->message = $objectManager->create('Magento\Framework\Mail\MessageInterface');

        $arrContextOptions = [
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ]
        ];

        try {
            if (method_exists($this->message, 'createAttachment')) {
                $this->message->createAttachment(
                    file_get_contents($body, false, stream_context_create($arrContextOptions)),
                    \Zend_Mime::TYPE_OCTETSTREAM,
                    \Zend_Mime::DISPOSITION_ATTACHMENT,
                    \Zend_Mime::ENCODING_BASE64,
                    basename($filename)
                );
            } else {
                if (version_compare($version, '2.3.3') >= 0) {
                    if (version_compare($version, '2.3.6') >= 0 || version_compare($version, '2.4.1') >= 0) {
                        $attachmentPart = $objectManager->create(\Laminas\Mime\Part::class);
                        $attachmentPart->setContent(file_get_contents($body, false, stream_context_create($arrContextOptions)))
                            ->setType($fileType)
                            ->setFileName($filename)
                            ->setDisposition(\Laminas\Mime\Mime::DISPOSITION_ATTACHMENT)
                            ->setEncoding(\Laminas\Mime\Mime::ENCODING_BASE64);
                    } else {
                        $this->partFactory = $objectManager->get(\Zend\Mime\PartFactory::class);
                        $attachmentPart = $this->partFactory->create();
                        $attachmentPart->setContent(file_get_contents($body, false, stream_context_create($arrContextOptions)))
                            ->setType($fileType)
                            ->setFileName($filename)
                            ->setDisposition(\Zend\Mime\Mime::DISPOSITION_ATTACHMENT)
                            ->setEncoding(\Zend\Mime\Mime::ENCODING_BASE64);
                    }
                    $this->attachments[] = $attachmentPart;
                } else {
                    $attachment = new \Zend\Mime\Part(file_get_contents($body, false, stream_context_create($arrContextOptions)));
                    $attachment->encoding = \Zend\Mime\Mime::ENCODING_BASE64;
                    $attachment->type = \Zend\Mime\Mime::TYPE_OCTETSTREAM;
                    $attachment->disposition = \Zend\Mime\Mime::DISPOSITION_ATTACHMENT;
                    $attachment->filename = basename($filename);
                    $this->parts[] = $attachment;
                }
            }
            return $this;
        } catch (\Exception $e) {
            ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info($e->getMessage());
        }
    }

    /**
     * Prepare message.
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function prepareMessage()
    {
        $objectManager = ObjectManager::getInstance();
        $version = $this->getVersion();

        if (version_compare($version, '2.3.3') >= 0) {
            $mimePartInterfaceFactory = $objectManager->get('Magento\Framework\Mail\MimePartInterfaceFactory');
            $mimeMessageInterfaceFactory = $objectManager->get('Magento\Framework\Mail\MimeMessageInterfaceFactory');
            $emailMessageInterfaceFactory = $objectManager->get('Magento\Framework\Mail\EmailMessageInterfaceFactory');

            $template = $this->getTemplate();
            $content = $template->processTemplate();
            switch ($template->getType()) {
                case \Magento\Framework\App\TemplateTypesInterface::TYPE_TEXT:
                    $part['type'] = \Magento\Framework\Mail\MimeInterface::TYPE_TEXT;
                    break;

                case \Magento\Framework\App\TemplateTypesInterface::TYPE_HTML:
                    $part['type'] = \Magento\Framework\Mail\MimeInterface::TYPE_HTML;
                    break;

                default:
                    throw new \Magento\Framework\Exception\LocalizedException(
                        new \Magento\Framework\Phrase('Unknown template type')
                    );
            }
            $mimePart = $mimePartInterfaceFactory->create(['content' => $content]);
            $parts = sizeof($this->attachments) ? array_merge([$mimePart], $this->attachments) : [$mimePart];
            $this->messageData['body'] = $mimeMessageInterfaceFactory->create(
                ['parts' => $parts]
            );

            $this->messageData['subject'] = html_entity_decode(
                (string)$template->getSubject(),
                ENT_QUOTES
            );
            $this->message = $emailMessageInterfaceFactory->create($this->messageData);
        } else {
            parent::prepareMessage();
            if (!empty($this->parts)) {
                foreach ($this->parts as $part) {
                    if (!is_null($this->message->getBody())) {
                        $this->message->getBody()->addPart($part);
                    }
                }
                if (!is_null($this->message->getBody())) {
                    $this->message->setBody($this->message->getBody());
                }
            }
        }
        return $this;
    }

    /**
     * Add cc address
     *
     * @param array|string $address
     * @param string $name
     *
     * @return $this
     */
    public function addCc($address, $name = '')
    {
        if (version_compare($this->getVersion(), '2.3.3') >= 0) {
            $this->addAddressByType('cc', $address, $name);
        } else {
            $this->message->addCc($address, $name);
        }
        return $this;
    }

    /**
     * Add to address
     *
     * @param array|string $address
     * @param string $name
     *
     * @return $this
     */
    public function addTo($address, $name = '')
    {
        if (version_compare($this->getVersion(), '2.3.3') >= 0) {
            $this->addAddressByType('to', $address, $name);
        } else {
            $this->message->addTo($address, $name);
        }
        return $this;
    }

    /**
     * Add bcc address
     *
     * @param array|string $address
     *
     * @return $this
     */
    public function addBcc($address)
    {
        if (version_compare($this->getVersion(), '2.3.3') >= 0) {
            $this->addAddressByType('bcc', $address);
        } else {
            $this->message->addBcc($address);
        }
        return $this;
    }

    /**
     * Set Reply-To Header
     *
     * @param string $email
     * @param string|null $name
     *
     * @return $this
     */
    public function setReplyTo($email, $name = null)
    {
        if (version_compare($this->getVersion(), '2.3.3') >= 0) {
            $this->addAddressByType('replyTo', $email, $name);
        } else {
            $this->message->setReplyTo($email, $name);
        }
        return $this;
    }

    /**
     * Set mail from address
     *
     * @param string|array $from
     *
     * @return $this
     * @throws \Magento\Framework\Exception\MailException
     * @see setFromByScope()
     *
     * @deprecated 102.0.1 This function sets the from address but does not provide
     * a way of setting the correct from addresses based on the scope.
     */
    public function setFrom($from)
    {
        return $this->setFromByScope($from);
    }

    /**
     * Set mail from address by scopeId
     *
     * @param string|array $from
     * @param string|int $scopeId
     *
     * @return $this
     * @throws \Magento\Framework\Exception\MailException
     * @since 102.0.1
     */
    public function setFromByScope($from, $scopeId = null)
    {
        $result = $this->_senderResolver->resolve($from, $scopeId);
        if (version_compare($this->getVersion(), '2.3.3') >= 0) {
            $this->addAddressByType('from', $result['email'], $result['name']);
        } else {
            if (version_compare($this->getVersion(), '2.2.8') <= 0) {
                $this->message->setFrom($result['email'], $result['name']);
            } else {
                $this->message->setFromAddress($result['email'], $result['name']);
            }
        }
        return $this;
    }

    /**
     * Handles possible incoming types of email (string or array)
     *
     * @param string $addressType
     * @param string|array $email
     * @param string|null $name
     */
    private function addAddressByType($addressType, $email, $name = null)
    {
        $objectManager = ObjectManager::getInstance();
        $addressConverter = $objectManager->get('Magento\Framework\Mail\AddressConverter');

        if (is_string($email)) {
            $this->messageData[$addressType][] = $addressConverter->convert($email, $name);
            return;
        }
        $convertedAddressArray = $addressConverter->convertMany($email);
        if (isset($this->messageData[$addressType])) {
            $this->messageData[$addressType] = array_merge(
                $this->messageData[$addressType],
                $convertedAddressArray
            );
        }
    }

    /**
     * @return mixed
     */
    private function getVersion()
    {
        $objectManager = ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        return $productMetadata->getVersion();
    }
}
