//
//  AppDelegate.m
//  ssh-askpass
//
//  Created by johnny ^_^ on 2013-06-18.
//  Copyright (c) 2013 Jan Skalny. All rights reserved.
//

#import "AppDelegate.h"

@implementation AppDelegate

- (void)applicationDidFinishLaunching:(NSNotification *)aNotification
{
    NSArray *args = [[NSProcessInfo processInfo] arguments];
    NSButton *allowButton, *denyButton;
    NSAlert *alert = [[NSAlert alloc] init];
     
    allowButton = [alert addButtonWithTitle:@"Allow"];
    denyButton = [alert addButtonWithTitle:@"Deny"];
    [allowButton setKeyEquivalent:@"y"];
    
    [alert setAlertStyle:NSWarningAlertStyle];
    [alert setMessageText:@"OpenSSH"];
    if ([args count] > 1) {
        [alert setInformativeText:[args objectAtIndex:1]];
    }
    [[NSApplication sharedApplication] activateIgnoringOtherApps:YES];
    [[NSRunningApplication currentApplication] activateWithOptions:NSApplicationActivateIgnoringOtherApps];
     
    if ([alert runModal] == NSAlertFirstButtonReturn) {
        printf("yes\n");
    } else {
        printf("no");
    }
     
    exit(0);
    
    // Insert code here to initialize your application
}

@end
